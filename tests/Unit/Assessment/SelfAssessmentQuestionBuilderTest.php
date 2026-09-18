<?php

namespace Tests\Unit\Assessment;

use Tests\TestCase;

/**
 * หมายเหตุ 2026-09-01: เทสต์ 2 ตัวที่เช็ค markup ของ question builder เวอร์ชันฟอร์ม Blade
 * (name="full_score", <option value="score|na">, $nextQuestionNumber, $activeQuestionLevel)
 * ถูกถอดออกแล้ว เพราะ builder ถูกเขียนใหม่เป็น editor แบบ JS ทั้งชุด (data-qs-* + route questions.set)
 * ตัวที่เหลือเช็คพฤติกรรมจริง ไม่ผูกกับหน้าตา จึงยังใช้ได้
 */
class SelfAssessmentQuestionBuilderTest extends TestCase
{
    public function test_incomplete_questions_stay_out_of_employee_form_and_formula_uses_question_full_score(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Assessment/AssessmentSelfController.php'));
        $form = file_get_contents(resource_path('views/assessment/self-user.blade.php'));

        $this->assertGreaterThanOrEqual(2, substr_count($controller, "->whereHas('choices')"));
        $this->assertStringContainsString('$fullScore = (float) $question->full_score;', $form);
    }
}
