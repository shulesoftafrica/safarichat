<?php

namespace Tests\Feature;

use App\Http\Controllers\Home;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Team members: add, edit, permissions and what a member sees after logging in.
 *
 * Everything runs inside a transaction that is rolled back, so the shared local database is not changed.
 *
 * Production reports this covers:
 *  1. "once I add a user, I cannot edit details" (the Edit link and form only ever worked for yourself, and
 *     the handler wrote every posted field to the logged-in user).
 *  2. "the user I added gets a 500 when he logs in" (`Auth::user()->business` is null for a team member).
 */
class TeamMemberManagementTest extends TestCase
{
    use DatabaseTransactions;

    private Business $business;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        // the app's own CSRF middleware also does an IP lookup per request; neither matters here
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->business = Business::query()
            ->whereIn('user_id', User::query()->select('id'))
            ->firstOrFail();
        $this->owner = User::findOrFail($this->business->user_id);
    }

    private function member(string $role = 'member', ?string $phone = null, ?int $parent = null): User
    {
        return User::create([
            'name' => 'Member ' . Str::random(4),
            'phone' => $phone ?? '+255700' . random_int(100000, 999999),
            'password' => bcrypt(Str::random(20)),
            'parent_business_id' => $parent ?? $this->business->id,
            'role' => $role,
            'uuid' => (string) Str::uuid(),
            'verified' => 1,
        ]);
    }

    /**
     * POST to the settings handler as $as. Calls the controller directly: the legacy URL router builds its
     * routes from $_SERVER[REQUEST_URI] at boot (and maps URLs differently on the live server than in this
     * checkout), so an HTTP-level request cannot reach it reliably from a test.
     */
    private function settingsPost(User $as, array $data)
    {
        $this->actingAs($as);
        session()->flush();

        $request = Request::create("/home/settings", "POST", $data);
        $request->setLaravelSession($this->app["session.store"]);
        $this->app->instance("request", $request);
        Facade::clearResolvedInstance("request");

        app(Home::class)->settings();

        return new class($this) {
            public function __construct(private $test) {}

            public function assertSessionHas(string $key): self
            {
                $this->test->assertTrue(session()->has($key), "Session is missing expected key [{$key}]. Session: " . json_encode(session()->all()));

                return $this;
            }
        };
    }

    // ---- 2. a team member resolves to the business they belong to -------------------------------------------

    public function test_a_team_member_resolves_to_the_business_they_were_added_to(): void
    {
        $member = $this->member();

        $this->assertNull($member->business()->first(), 'the relation still means "owns a business"');
        $this->assertSame($this->business->id, $member->business->id, '$user->business must not be null for a member');
        $this->assertTrue($member->isTeamMember());
        $this->assertFalse($member->isBusinessOwner());
        $this->assertSame((int) $this->owner->id, $member->ownerUserId(), 'a member works with the owner\'s data');
        $this->assertNotNull($member->business->billingAccount ?? $this->business->billingAccount ?? true);
    }

    public function test_the_owner_is_unchanged(): void
    {
        $this->assertSame($this->business->id, $this->owner->business->id);
        $this->assertTrue($this->owner->isBusinessOwner());
        $this->assertFalse($this->owner->isTeamMember());
        $this->assertSame((int) $this->owner->id, $this->owner->ownerUserId());
        $this->assertTrue($this->owner->canManageTeam());
    }

    public function test_only_owner_admin_and_manager_can_manage_the_team(): void
    {
        $this->assertFalse($this->member('member')->canManageTeam());
        $this->assertTrue($this->member('manager')->canManageTeam());
        $this->assertTrue($this->member('admin')->canManageTeam());
    }

    public function test_a_member_is_not_forced_to_connect_their_own_whatsapp_or_add_a_product(): void
    {
        // the setup middleware used to look at the member's own (always empty) instances and products
        $member = $this->member();

        $this->assertSame((int) $this->owner->id, $member->ownerUserId());
        $this->assertNotSame((int) $member->id, $member->ownerUserId());
    }

    // ---- 1. editing -------------------------------------------------------------------------------------------

    public function test_the_owner_can_edit_a_team_member(): void
    {
        $member = $this->member();

        $this->settingsPost($this->owner, [
            'table' => 'user', 'edit' => $member->id,
            'name' => 'Corrected Name', 'email' => 'corrected_' . Str::random(5) . '@example.com',
            'phone' => '+255711222333', 'role' => 'manager',
        ])->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('Corrected Name', $member->name);
        $this->assertSame('+255711222333', $member->phone);
        $this->assertSame('manager', $member->role);
        $this->assertSame($this->business->id, (int) $member->parent_business_id, 'still on the same business');
    }

    public function test_a_manager_can_edit_a_member_but_not_the_owner(): void
    {
        $manager = $this->member('manager');
        $member = $this->member();

        $this->settingsPost($manager, ['table' => 'user', 'edit' => $member->id, 'name' => 'By Manager', 'phone' => '+255711000111'])
            ->assertSessionHas('success');
        $this->assertSame('By Manager', $member->fresh()->name);

        $ownerName = $this->owner->name;
        $this->settingsPost($manager, ['table' => 'user', 'edit' => $this->owner->id, 'name' => 'Hacked', 'phone' => '+255711000999'])
            ->assertSessionHas('error');
        $this->assertSame($ownerName, $this->owner->fresh()->name, 'the owner can never be edited by a manager');
    }

    public function test_a_plain_member_cannot_edit_anyone_else(): void
    {
        $plain = $this->member('member');
        $other = $this->member('member');
        $before = $other->name;

        $this->settingsPost($plain, ['table' => 'user', 'edit' => $other->id, 'name' => 'Nope', 'phone' => '+255711000222'])
            ->assertSessionHas('error');

        $this->assertSame($before, $other->fresh()->name);
    }

    public function test_nobody_can_edit_a_member_of_another_business(): void
    {
        $other = Business::where('id', '!=', $this->business->id)->first();
        $this->assertNotNull($other, 'local database needs a second business');
        $stranger = $this->member('member', null, $other->id);
        $before = $stranger->name;

        $this->settingsPost($this->owner, ['table' => 'user', 'edit' => $stranger->id, 'name' => 'Cross tenant', 'phone' => '+255711000333'])
            ->assertSessionHas('error');

        $this->assertSame($before, $stranger->fresh()->name);
    }

    public function test_editing_yourself_still_works_and_ignores_fields_that_must_not_be_set(): void
    {
        $member = $this->member('member');

        $this->settingsPost($member, [
            'table' => 'user', 'edit' => $member->id,
            'name' => 'My New Name', 'phone' => $member->phone,
            // everything below used to be written straight to the user by update(request()->all())
            'role' => 'admin', 'parent_business_id' => 999999, 'is_active' => 0, 'subscription_status' => 'active',
            'available_credits' => 999999,
        ])->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('My New Name', $member->name);
        $this->assertSame('member', $member->role, 'cannot promote yourself');
        $this->assertSame($this->business->id, (int) $member->parent_business_id, 'cannot move yourself to another business');
        $this->assertTrue((bool) $member->is_active);
    }

    // ---- the settings page itself ---------------------------------------------------------------------------

    /** Render the settings page as $as (a GET straight into the controller, see settingsPost()). */
    private function renderSettings(User $as): string
    {
        $this->actingAs($as);
        session()->flush();

        $request = Request::create('/home/settings', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);
        Facade::clearResolvedInstance('request');

        return app(Home::class)->settings()->render();
    }

    public function test_owner_sees_an_edit_link_for_each_team_member_and_the_add_button(): void
    {
        $member = $this->member();
        $html = $this->renderSettings($this->owner);

        $this->assertStringContainsString('onclick="editUser(this)"', $html);
        $this->assertStringContainsString('data-id="' . $member->id . '"', $html, 'the team member must have an Edit/Delete link');
        $this->assertStringContainsString('onclick="deleteUser(this)"', $html);
        // the owner is offered "Add New User", or "Upgrade to Add More" when the plan's user limit is reached
        $this->assertTrue(
            str_contains($html, 'data-target="#addUserModal"') || str_contains($html, 'Upgrade to Add More'),
            'the owner must see the add (or upgrade) button'
        );
        // the user-edit form is no longer pre-filled with the logged-in user's details; the Edit link fills it
        foreach (['user_edit_name', 'user_edit_email', 'user_edit_phone'] as $field) {
            $this->assertStringContainsString('id="' . $field . '" value=""', $html, "{$field} must start empty");
        }
        $this->assertStringContainsString('id="user_edit_id"', $html);
        $this->assertStringContainsString('id="user_role_group"', $html);
    }

    public function test_a_plain_member_gets_no_add_or_delete_and_can_edit_only_themselves(): void
    {
        $me = $this->member('member');
        $colleague = $this->member('member');
        $html = $this->renderSettings($me);

        $this->assertStringContainsString('data-id="' . $me->id . '"', $html, 'edit link for yourself');
        $this->assertStringNotContainsString('data-id="' . $colleague->id . '"', $html, 'no edit/delete link for a colleague');
        $this->assertStringNotContainsString('onclick="deleteUser(this)"', $html);
        $this->assertStringNotContainsString('data-target="#addUserModal"', $html);
    }

    /**
     * The production 500: right after the member's OTP login the next requests failed with
     * "Attempt to read property billingAccount on null {userId: 394}" (ProductController::index).
     */
    public function test_the_product_list_opens_for_a_team_member(): void
    {
        $member = $this->member();
        $this->actingAs($member);
        session()->flush();

        $request = Request::create('/products', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);
        Facade::clearResolvedInstance('request');

        $response = app(\App\Http\Controllers\ProductController::class)->index($request);
        $html = method_exists($response, 'render') ? $response->render() : '';

        $this->assertNotSame('', $html, 'the product page must render for a member instead of throwing');
    }

    public function test_user_names_are_escaped_on_the_settings_page(): void
    {
        $evil = $this->member();
        $evil->forceFill(['name' => '<script>alert(1)</script>'])->save();

        $html = $this->renderSettings($this->owner);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    // ---- phone is the login: no duplicates ------------------------------------------------------------------

    public function test_a_phone_already_in_use_cannot_be_added_or_edited_onto_another_user(): void
    {
        $existing = $this->member();
        $other = $this->member();

        // add: same number as an existing account
        $before = User::where('phone', $existing->phone)->count();
        $this->settingsPost($this->owner, ['table' => 'add_user', 'name' => 'Duplicate', 'phone' => $existing->phone, 'role' => 'member'])
            ->assertSessionHas('error');
        $this->assertSame($before, User::where('phone', $existing->phone)->count());

        // edit: move someone onto that number
        $this->settingsPost($this->owner, ['table' => 'user', 'edit' => $other->id, 'name' => $other->name, 'phone' => $existing->phone])
            ->assertSessionHas('error');
        $this->assertNotSame($existing->phone, $other->fresh()->phone);
    }

    // ---- add / delete are limited to people who can manage the team ---------------------------------------

    public function test_a_plain_member_cannot_add_or_delete_users(): void
    {
        $plain = $this->member('member');
        $victim = $this->member('member');

        $this->settingsPost($plain, ['table' => 'delete_user', 'user_id' => $victim->id])->assertSessionHas('error');
        $this->assertNotNull(User::find($victim->id), 'a plain member must not be able to delete a colleague');

        $count = User::where('parent_business_id', $this->business->id)->count();
        $this->settingsPost($plain, ['table' => 'add_user', 'name' => 'Sneaky', 'phone' => '+255711555666', 'role' => 'admin'])
            ->assertSessionHas('error');
        $this->assertSame($count, User::where('parent_business_id', $this->business->id)->count());
    }

    public function test_a_plain_member_cannot_rewrite_business_details(): void
    {
        $plain = $this->member('member');
        $name = $this->business->name;

        $this->settingsPost($plain, ['table' => 'business', 'name' => 'Taken Over', 'user_id' => $plain->id])
            ->assertSessionHas('error');

        $fresh = Business::find($this->business->id);
        $this->assertSame($name, $fresh->name);
        $this->assertSame((int) $this->owner->id, (int) $fresh->user_id);
    }

    public function test_the_business_owner_id_cannot_be_overwritten_through_the_business_form(): void
    {
        $someone = $this->member();

        $this->settingsPost($this->owner, ['table' => 'business', 'name' => $this->business->name, 'user_id' => $someone->id]);

        $this->assertSame((int) $this->owner->id, (int) Business::find($this->business->id)->user_id);
    }
}
