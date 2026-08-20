<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Seeds ShuleSoft's real module catalog as pitchable "offers" for the
 * Next-Best-Offer rotation engine (see config/sales_rotation.php).
 *
 * Each ShuleSoft module becomes one active `products` row with an authored
 * hook + pain point + features so the AI can rotate angles instead of
 * repeating one generic "ShuleSoft" pitch.
 *
 * Idempotent: matches by (user_id, name-aliases) so re-runs enrich in place
 * rather than duplicating. The two pre-existing generic rows
 * ("shulesoft", "universal control number") are repurposed, not duplicated.
 *
 * OWNER RESOLUTION (portable across local / staging / live) — in order:
 *   1. env SHULESOFT_OWNER_USER_ID (+ optional SHULESOFT_OWNER_BUSINESS_ID)
 *   2. Business row matched by name "ShuleSoft" or SHULESOFT_OWNER_EMAIL
 * If neither resolves, the seeder ABORTS (it never guesses an owner) so it can
 * be run safely on the live database without hardcoded IDs.
 *
 * Run:  php artisan db:seed --class="Database\\Seeders\\ShuleSoftModuleCatalogSeeder"
 */
class ShuleSoftModuleCatalogSeeder extends Seeder
{
    private int $ownerUserId = 0;
    private ?int $ownerBusinessId = null;

    public function run(): void
    {
        [$this->ownerUserId, $this->ownerBusinessId] = $this->resolveOwner();

        if ($this->ownerUserId <= 0) {
            $msg = 'ShuleSoftModuleCatalogSeeder: could not resolve the ShuleSoft owner. '
                 . 'Set SHULESOFT_OWNER_USER_ID (and optionally SHULESOFT_OWNER_BUSINESS_ID) in .env, '
                 . 'or ensure a Business named "ShuleSoft" exists. Aborting — nothing was written.';
            $this->command?->error($msg);
            Log::warning($msg);
            return;
        }

        $this->command?->info("ShuleSoft owner resolved: user_id={$this->ownerUserId}, business_id=" . ($this->ownerBusinessId ?? 'null'));

        $modules = $this->modules();

        $idByKey = [];

        // Pass 1 — upsert every module (without upsell chains yet).
        foreach ($modules as $key => $m) {
            $product = $this->upsertByNames($m['name_aliases'], [
                'user_id'            => $this->ownerUserId,
                'business_id'        => $this->ownerBusinessId,
                'name'               => $m['name'],
                'category'           => $m['category'],
                'target_industry'    => 'education',
                'product_type'       => 'service',
                'status'             => 'active',
                'retail_price'       => 0,
                'minimal_description'=> $m['minimal'],
                'description'        => $m['ai_description'],
                'ai_description'     => $m['ai_description'],
                'campaign_hook_text' => $m['hook'],
                'campaign_pain_point'=> $m['pain'],
                'key_features'       => $m['features'],
                'common_objections'  => $m['objections'],
                'selling_points'     => $m['selling_points'],
            ]);

            $idByKey[$key] = $product->id;
        }

        // Pass 2 — wire the rotation chains (upsell_products) by resolving keys → ids.
        foreach ($modules as $key => $m) {
            $nextIds = [];
            foreach ($m['next'] as $nextKey) {
                if (isset($idByKey[$nextKey])) {
                    $nextIds[] = $idByKey[$nextKey];
                }
            }

            Product::where('id', $idByKey[$key])->update([
                'upsell_products' => json_encode($nextIds),
            ]);
        }

        $this->command?->info('Seeded ' . count($modules) . ' ShuleSoft modules for user_id=' . $this->ownerUserId . '.');
        Log::info('ShuleSoftModuleCatalogSeeder completed', ['modules' => count($modules), 'ids' => $idByKey]);
    }

    /**
     * Resolve the ShuleSoft owner (user_id, business_id) without hardcoding.
     *
     * @return array{0:int,1:?int}
     */
    private function resolveOwner(): array
    {
        // 1. Explicit override via config (recommended for production runs).
        //    Read through config() so it survives `php artisan config:cache`.
        $cfgUserId = (int) config('sales_rotation.shulesoft_owner.user_id', 0);
        if ($cfgUserId > 0) {
            $cfgBusinessId = (int) config('sales_rotation.shulesoft_owner.business_id', 0);
            return [$cfgUserId, $cfgBusinessId > 0 ? $cfgBusinessId : null];
        }

        // 2. Look up the business by name "ShuleSoft" (or a configured email).
        $email = config('sales_rotation.shulesoft_owner.email');
        $business = Business::query()
            ->when($email, fn ($q) => $q->orWhere('email', $email))
            ->whereRaw('LOWER(name) = ?', ['shulesoft'])
            ->first();

        if ($business && $business->user_id) {
            return [(int) $business->user_id, (int) $business->id];
        }

        return [0, null];
    }

    /**
     * Upsert a product matched by ANY of the given name aliases (case-insensitive)
     * for this owner, so pre-existing generic rows get repurposed in place.
     */
    private function upsertByNames(array $nameAliases, array $attributes): Product
    {
        $existing = Product::where('user_id', $this->ownerUserId)
            ->where(function ($q) use ($nameAliases) {
                foreach ($nameAliases as $alias) {
                    $q->orWhereRaw('LOWER(name) = ?', [mb_strtolower($alias)]);
                }
            })
            ->first();

        if ($existing) {
            $existing->fill($attributes);
            $existing->save();
            return $existing;
        }

        return Product::create($attributes);
    }

    /**
     * The catalog. `next` lists the module keys to rotate to after this one.
     */
    private function modules(): array
    {
        return [
            'platform' => [
                'name' => 'ShuleSoft School Management System',
                'name_aliases' => ['shulesoft', 'ShuleSoft School Management System', 'ShuleSoft Platform'],
                'category' => 'Platform',
                'minimal' => 'All-in-one school management platform.',
                'ai_description' => 'ShuleSoft is an all-in-one school management system used by schools across Tanzania to run admissions, fees, exams, finance, payroll and HR from one place — with parent communication over SMS and WhatsApp.',
                'hook' => 'Hi {name}! Most schools run admissions, fees, exams and payroll on separate books that never agree. ShuleSoft brings them into one system your whole team shares. Can I show you a 5-minute overview?',
                'pain' => 'Running the school on disconnected registers, Excel sheets and manual receipts that never reconcile.',
                'features' => ['One system for academics + finance + HR', 'Parent SMS/WhatsApp updates', 'Works on low bandwidth', 'Role-based access for staff'],
                'objections' => ['We already use Excel', 'Our internet is unreliable', 'Staff are not tech savvy'],
                'selling_points' => ['Used by schools across Tanzania', 'Local support', 'One login for the whole school'],
                'next' => ['admission', 'fee_collection', 'exam'],
            ],
            'admission' => [
                'name' => 'Online Admission Management',
                'name_aliases' => ['Online Admission Management', 'admission', 'online admission'],
                'category' => 'Academic',
                'minimal' => 'Digital student admission & enrollment.',
                'ai_description' => 'ShuleSoft Online Admission lets parents apply and enroll digitally, auto-generates student registration numbers, and moves accepted pupils straight into the class register — no paper forms.',
                'hook' => 'Hi {name}! Admissions season buries the office in paper forms and duplicate student records. ShuleSoft Admission lets parents apply online and creates the student file automatically. Want to see how it cuts the workload?',
                'pain' => 'Paper admission forms, duplicate student records and manual registration-number generation every intake.',
                'features' => ['Online application forms', 'Auto student registration numbers', 'Instant class allocation', 'Applicant tracking'],
                'objections' => ['Parents prefer coming in person', 'We only admit once a year'],
                'selling_points' => ['No duplicate records', 'Faster intake', 'Clean data from day one'],
                'next' => ['exam', 'fee_collection'],
            ],
            'fee_collection' => [
                'name' => 'Fee Collection & Management',
                'name_aliases' => ['Fee Collection & Management', 'fee collection', 'fees'],
                'category' => 'Finance',
                'minimal' => 'Track invoices, payments and fee balances.',
                'ai_description' => 'ShuleSoft Fee Collection issues fee invoices, records every payment, and shows real-time balances per student — with automatic SMS reminders to parents who are behind.',
                'hook' => 'Hi {name}! How many hours does the bursar lose chasing fee balances and writing receipts by hand? ShuleSoft tracks every student\'s fees automatically and reminds parents by SMS. Worth a quick look?',
                'pain' => 'Fee arrears you cannot see in real time, manual receipts, and endless follow-up with parents.',
                'features' => ['Per-student fee balances', 'Automatic SMS reminders', 'Digital receipts', 'Fee structure per class/term'],
                'objections' => ['Parents pay in cash', 'We track fees in a book'],
                'selling_points' => ['See arrears instantly', 'Fewer disputes', 'Faster collection'],
                'next' => ['ucn', 'bank_integration'],
            ],
            'ucn' => [
                'name' => 'Universal Control Number (UCN) Payments',
                'name_aliases' => ['universal control number', 'Universal Control Number (UCN) Payments', 'ucn'],
                'category' => 'Payments',
                'minimal' => 'One control number, pay from any bank or mobile money.',
                'ai_description' => 'ShuleSoft Universal Control Number (UCN) gives each fee invoice a single control number parents can pay from any bank or mobile money in Tanzania — and the payment posts to the student\'s account automatically.',
                'hook' => 'Hi {name}! Parents struggle when each bank needs a different account, and matching payments to students is a nightmare. With ShuleSoft UCN, one control number works across every bank and mobile money, and posts automatically. Shall I show you?',
                'pain' => 'Payments scattered across many bank accounts and hours spent matching deposits to the right student.',
                'features' => ['One number, any bank or mobile money', 'Auto-reconciled to the student', 'Real-time payment confirmation', 'No more mismatched deposits'],
                'objections' => ['We already have a bank account', 'Parents know our account number'],
                'selling_points' => ['Pay from anywhere', 'Zero manual matching', 'Instant confirmation'],
                'next' => ['bank_integration', 'reconciliation'],
            ],
            'bank_integration' => [
                'name' => 'Bank Integration (Mkombozi & CRDB)',
                'name_aliases' => ['Bank Integration (Mkombozi & CRDB)', 'bank integration'],
                'category' => 'Payments',
                'minimal' => 'Direct integration with Mkombozi and CRDB.',
                'ai_description' => 'ShuleSoft integrates directly with Mkombozi Bank and CRDB so fee payments made at the bank flow straight into the school system in real time — no manual entry, no waiting for statements.',
                'hook' => 'Hi {name}! When parents pay at Mkombozi or CRDB, does your office still key it in by hand? ShuleSoft connects directly to both banks so payments appear instantly. Can I show you the integration?',
                'pain' => 'Waiting for bank statements and re-typing every bank payment into the school ledger.',
                'features' => ['Direct Mkombozi & CRDB integration', 'Real-time payment posting', 'No manual entry', 'Fewer errors'],
                'objections' => ['We reconcile manually already', 'We use a different bank'],
                'selling_points' => ['Real-time bank feeds', 'Less data entry', 'Trusted local banks'],
                'next' => ['reconciliation', 'budget'],
            ],
            'reconciliation' => [
                'name' => 'Bank Reconciliation',
                'name_aliases' => ['Bank Reconciliation', 'reconciliation'],
                'category' => 'Finance',
                'minimal' => 'Match bank statements to school records automatically.',
                'ai_description' => 'ShuleSoft Bank Reconciliation automatically matches bank statements against fee collections and expenses, flagging any difference so the accountant closes the books in minutes, not days.',
                'hook' => 'Hi {name}! Month-end reconciliation eating your accountant\'s week? ShuleSoft matches bank statements to school records automatically and flags the gaps. Want a quick demo?',
                'pain' => 'Days lost every month manually matching bank statements to the school\'s books.',
                'features' => ['Automatic statement matching', 'Discrepancy flags', 'Faster month-end close', 'Audit-ready reports'],
                'objections' => ['Our accountant handles it', 'We are a small school'],
                'selling_points' => ['Close books faster', 'Catch errors early', 'Audit ready'],
                'next' => ['budget', 'accounting'],
            ],
            'budget' => [
                'name' => 'Budgeting & Expenditure Control',
                'name_aliases' => ['Budgeting & Expenditure Control', 'budget', 'budgeting'],
                'category' => 'Finance',
                'minimal' => 'Plan budgets and control spending against them.',
                'ai_description' => 'ShuleSoft Budgeting lets the school set budgets per department and blocks or flags spending that exceeds them, so the head teacher and board always know where the money is going.',
                'hook' => 'Hi {name}! Does spending often overshoot what the board approved? ShuleSoft lets you set budgets and see spending against them in real time. Shall I show you?',
                'pain' => 'No visibility of spending against the approved budget until it is already overspent.',
                'features' => ['Department budgets', 'Real-time spend vs budget', 'Overspend alerts', 'Board-ready reports'],
                'objections' => ['The bursar tracks this', 'Our budget rarely changes'],
                'selling_points' => ['Control overspending', 'Board transparency', 'Plan with confidence'],
                'next' => ['accounting', 'payroll'],
            ],
            'accounting' => [
                'name' => 'Accounting',
                'name_aliases' => ['Accounting', 'accounts'],
                'category' => 'Finance',
                'minimal' => 'Full school accounting and financial statements.',
                'ai_description' => 'ShuleSoft Accounting turns every fee payment, expense and payroll entry into a proper set of books — income statement, balance sheet and cash flow — ready for auditors and the board.',
                'hook' => 'Hi {name}! When auditors ask for statements, is it a scramble? ShuleSoft Accounting keeps proper books automatically from the fees and expenses you already record. Want to see the reports?',
                'pain' => 'No proper financial statements — auditors and the board wait while books are assembled by hand.',
                'features' => ['Income statement & balance sheet', 'Auto entries from fees & payroll', 'Audit-ready', 'Multi-year comparison'],
                'objections' => ['We hire an external accountant', 'QuickBooks does this'],
                'selling_points' => ['Books built automatically', 'Audit ready', 'Made for schools'],
                'next' => ['payroll', 'hr'],
            ],
            'exam' => [
                'name' => 'Examination & Results Management',
                'name_aliases' => ['Examination & Results Management', 'exam', 'examination', 'exams'],
                'category' => 'Academic',
                'minimal' => 'Record marks and auto-generate report cards.',
                'ai_description' => 'ShuleSoft Examination records marks per subject, computes grades and positions automatically, and generates report cards parents can receive by SMS or WhatsApp — no more manual mark sheets.',
                'hook' => 'Hi {name}! End of term means teachers buried in mark sheets and hand-written report cards. ShuleSoft computes grades and positions automatically and sends results to parents. Can I show you?',
                'pain' => 'Manual mark sheets, error-prone grade computation and slow, hand-written report cards each term.',
                'features' => ['Auto grade & position', 'Report card generation', 'Results to parents by SMS/WhatsApp', 'Subject analytics'],
                'objections' => ['Teachers prefer their own sheets', 'We already print reports'],
                'selling_points' => ['No calculation errors', 'Instant report cards', 'Happy parents'],
                'next' => ['admission', 'fee_collection'],
            ],
            'payroll' => [
                'name' => 'Payroll Management',
                'name_aliases' => ['Payroll Management', 'payroll'],
                'category' => 'HR',
                'minimal' => 'Run staff salaries, PAYE and statutory deductions.',
                'ai_description' => 'ShuleSoft Payroll computes staff salaries, PAYE, NSSF and other deductions, generates payslips, and posts the cost straight into the school accounts each month.',
                'hook' => 'Hi {name}! Is payroll still a spreadsheet the bursar dreads every month? ShuleSoft computes salaries, PAYE and NSSF and generates payslips automatically. Worth a quick look?',
                'pain' => 'Manual monthly payroll with error-prone PAYE/NSSF calculations and no payslips.',
                'features' => ['Automatic PAYE & NSSF', 'Payslip generation', 'Posts to accounts', 'Staff salary history'],
                'objections' => ['We have few staff', 'Our accountant does payroll'],
                'selling_points' => ['No calculation errors', 'Payslips in one click', 'Statutory compliant'],
                'next' => ['hr', 'recruitment'],
            ],
            'hr' => [
                'name' => 'Human Resource (HR) Management',
                'name_aliases' => ['Human Resource (HR) Management', 'hr', 'human resource'],
                'category' => 'HR',
                'minimal' => 'Staff records, leave, attendance and contracts.',
                'ai_description' => 'ShuleSoft HR (newly launched) keeps every staff record, contract, leave balance and attendance in one place, with reminders for expiring contracts and licences.',
                'hook' => 'Hi {name}! We just launched ShuleSoft HR. Staff files, contracts, leave and attendance in one place — with alerts before contracts or licences expire. Can I give you an early look?',
                'pain' => 'Staff files scattered in cabinets, forgotten contract renewals and leave tracked on paper.',
                'features' => ['Central staff records', 'Leave & attendance', 'Contract expiry alerts', 'Document storage'],
                'objections' => ['We are a small team', 'The head teacher keeps files'],
                'selling_points' => ['Newly launched', 'Never miss a renewal', 'Everything in one place'],
                'next' => ['recruitment', 'payroll'],
            ],
            'recruitment' => [
                'name' => 'Recruitment Solution',
                'name_aliases' => ['Recruitment Solution', 'recruitment'],
                'category' => 'HR',
                'minimal' => 'Post vacancies, screen and hire teachers faster.',
                'ai_description' => 'ShuleSoft Recruitment (newly launched) lets the school post teaching vacancies, collect applications online, shortlist candidates and move a new hire straight into HR and Payroll.',
                'hook' => 'Hi {name}! Hiring teachers through stacks of paper CVs? Our new ShuleSoft Recruitment collects applications online, shortlists faster, and hands the new hire straight to HR and payroll. Want an early demo?',
                'pain' => 'Slow, manual teacher hiring with paper CVs and no pipeline from applicant to staff record.',
                'features' => ['Online vacancy posting', 'Application collection & shortlisting', 'Flows into HR & Payroll', 'Applicant history'],
                'objections' => ['We hire by referral', 'We rarely recruit'],
                'selling_points' => ['Newly launched', 'Hire faster', 'Seamless into HR'],
                'next' => ['hr', 'admission'],
            ],
        ];
    }
}
