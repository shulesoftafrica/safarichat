<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Production: a team member (Amos) opened "Add customer" and the Products box said "No results found", so a
 * customer could not be saved. Products were looked up with user_id = the logged-in user, and a team member
 * owns no products - they belong to the owner's business.
 *
 * Runs inside a rolled-back transaction, so the shared local database is not changed.
 */
class TeamMemberProductAccessTest extends TestCase
{
    use DatabaseTransactions;

    private Business $business;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::query()
            ->whereIn('user_id', User::query()->select('id'))
            ->firstOrFail();
        $this->owner = User::findOrFail($this->business->user_id);
    }

    private function member(?int $parentBusinessId): User
    {
        return User::create([
            'name' => 'Member ' . Str::random(4),
            'phone' => '+255700' . random_int(100000, 999999),
            'password' => bcrypt(Str::random(20)),
            'parent_business_id' => $parentBusinessId,
            'role' => 'member',
            'uuid' => (string) Str::uuid(),
            'verified' => 1,
        ]);
    }

    /**
     * Inserted with the query builder on purpose: the model's observer queues AI description jobs, which
     * must not run (or call out) from a test.
     */
    private function product(string $name, ?int $userId, ?int $businessId): Product
    {
        $id = DB::table('products')->insertGetId([
            'name' => $name,
            'sku' => 'T-' . Str::random(10),
            'category' => 'test',
            'description' => 'test product',
            'retail_price' => 1000,
            'wholesale_price' => 800,
            'status' => 'active',
            'user_id' => $userId,
            'business_id' => $businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Product::findOrFail($id);
    }

    public function test_team_member_can_see_products_owned_by_the_owner_and_the_business(): void
    {
        $member = $this->member($this->business->id);
        $byOwner = $this->product('Owner product', $this->owner->id, $this->business->id);
        $teammate = $this->member($this->business->id);
        $byBusinessOnly = $this->product('Teammate product', $teammate->id, $this->business->id);

        $this->assertFalse(Product::forUser($member->id)->exists(), 'precondition: the old scope finds nothing');

        $ids = Product::accessibleTo($member)->pluck('id')->all();

        $this->assertContains($byOwner->id, $ids);
        $this->assertContains($byBusinessOnly->id, $ids);
    }

    public function test_products_of_another_business_stay_hidden(): void
    {
        $member = $this->member($this->business->id);
        $otherBusiness = Business::where('id', '!=', $this->business->id)
            ->where('user_id', '!=', $this->owner->id)
            ->first();

        if (! $otherBusiness) {
            $this->markTestSkipped('needs a second business in the local database');
        }

        $foreign = $this->product('Foreign product', $otherBusiness->user_id, $otherBusiness->id);

        $this->assertNotContains($foreign->id, Product::accessibleTo($member)->pluck('id')->all());
    }

    public function test_owner_still_sees_their_own_products(): void
    {
        $own = $this->product('Own product', $this->owner->id, $this->business->id);

        $this->assertContains($own->id, Product::accessibleTo($this->owner)->pluck('id')->all());
    }

    public function test_user_without_any_business_only_gets_their_own_products(): void
    {
        $loner = $this->member(null);
        $mine = $this->product('Loner product', $loner->id, null);
        $other = $this->product('Not theirs', $this->owner->id, $this->business->id);

        $ids = Product::accessibleTo($loner)->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }
}
