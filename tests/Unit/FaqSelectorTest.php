<?php

namespace Tests\Unit;

use App\Services\FaqSelector;
use PHPUnit\Framework\TestCase;

/**
 * The AI prompt used to carry only the ten newest FAQs, so most of a business's FAQs never reached the AI.
 */
class FaqSelectorTest extends TestCase
{
    private function faq(int $id, string $question, string $answer, int $sort = 0): object
    {
        return (object) ['id' => $id, 'question' => $question, 'answer' => $answer, 'sort_order' => $sort];
    }

    private function many(int $count): \Illuminate\Support\Collection
    {
        return collect(range(1, $count))->map(fn ($i) => $this->faq($i, "Question number $i about topic$i", "Answer number $i with some words", $i));
    }

    public function test_every_faq_is_sent_when_they_fit(): void
    {
        $selected = (new FaqSelector())->select($this->many(26), 'hello');

        $this->assertCount(26, $selected);
        $this->assertSame(1, $selected->first()->id);
        $this->assertSame(26, $selected->last()->id);
    }

    public function test_faqs_beyond_the_old_limit_of_ten_are_included(): void
    {
        $ids = (new FaqSelector())->select($this->many(23), 'anything')->pluck('id')->all();

        $this->assertContains(11, $ids);
        $this->assertContains(23, $ids);
    }

    public function test_when_too_many_the_ones_matching_the_message_are_kept(): void
    {
        $faqs = $this->many(30)->push(
            $this->faq(99, 'How much does the premium plan cost?', 'Premium costs TZS 299,000 per month.', 99)
        );

        $selected = (new FaqSelector())->select($faqs, 'What is the premium plan price? cost please', maxChars: 600);

        $this->assertContains(99, $selected->pluck('id')->all(), 'the FAQ that answers the question must be sent');
        $this->assertLessThanOrEqual(600, $selected->sum(fn ($f) => mb_strlen($f->question) + mb_strlen($f->answer) + 8));
    }

    public function test_swahili_questions_match_swahili_faqs(): void
    {
        $faqs = $this->many(30)->push(
            $this->faq(77, 'Naweza kulipa vipi?', 'Unaweza kulipa kwa M-Pesa au benki.', 77)
        );

        $selected = (new FaqSelector())->select($faqs, 'Naomba kulipia vipi huduma hii', maxChars: 600);

        $this->assertContains(77, $selected->pluck('id')->all());
    }

    public function test_the_size_budget_is_respected_even_without_a_match(): void
    {
        $selected = (new FaqSelector())->select($this->many(60), 'zzz', maxChars: 900);

        $this->assertNotEmpty($selected);
        $this->assertLessThanOrEqual(900, $selected->sum(fn ($f) => mb_strlen($f->question) + mb_strlen($f->answer) + 8));
    }

    public function test_no_faqs_gives_an_empty_selection(): void
    {
        $this->assertCount(0, (new FaqSelector())->select(collect(), 'hello'));
    }
}
