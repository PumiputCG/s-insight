<?php

use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\Assessment\AssessmentQuestionController;
use App\Http\Controllers\Assessment\AssessmentRoundController;
use App\Http\Controllers\Assessment\AssessmentScoreController;
use App\Http\Controllers\Assessment\AssessmentSelfController;
use App\Http\Controllers\Assessment\AssessmentSettingController;
use Illuminate\Support\Facades\Route;

/*
| โมดูล Assessment (ระบบประเมินผล) — อยู่ในกลุ่ม middleware insight.auth (ครอบจาก routes/web.php)
| สิทธิ์เข้าถึงจริงคุมใน controller: admin / HR member (asm_members) / ตำแหน่งที่อนุญาต
*/
Route::prefix('assessment')->name('assessment.')->group(function () {
    Route::get('/', [AssessmentController::class, 'index'])->name('index');
    Route::get('/evaluate', [AssessmentController::class, 'evaluate'])->name('evaluate.index');
    // บันทึกคะแนนจากฟอร์มประเมิน (สิทธิ์ = ผู้ประเมินตามลำดับชั้น ตรวจใน controller)
    Route::put('/evaluate/value', [AssessmentController::class, 'evaluateSave'])->name('evaluate.save');
    Route::get('/evaluate/{employee}/{level}', [AssessmentController::class, 'evaluateShow'])
        ->where('level', '[12]')
        ->name('evaluate.show');
    Route::get('/review', [AssessmentController::class, 'review'])->name('review.index');

    // เปิดรอบ (sidebar) — ต้องเปิดรอบก่อนจึงจัดการระบบภายในได้
    Route::get('/rounds', [AssessmentRoundController::class, 'index'])->name('rounds.index');
    Route::post('/rounds', [AssessmentRoundController::class, 'store'])->name('rounds.store');
    Route::put('/rounds/{round}/close', [AssessmentRoundController::class, 'close'])->name('rounds.close');
    Route::put('/rounds/{round}/reopen', [AssessmentRoundController::class, 'reopen'])->name('rounds.reopen');
    Route::delete('/rounds/{round}', [AssessmentRoundController::class, 'destroy'])->name('rounds.destroy');

    // แท็บกำหนดคำถาม — คำถามต่อคอลัมน์ Input × ลำดับผู้ประเมิน (TH/EN/MY)
    Route::get('/questions', [AssessmentQuestionController::class, 'index'])->name('questions.index');
    Route::post('/questions', [AssessmentQuestionController::class, 'store'])->name('questions.store');
    // คำอธิบายประกอบฟอร์ม (คอลัมน์ × ลำดับผู้ประเมิน) + สวิตช์เปิด/ปิดให้ผู้ประเมินเห็น
    Route::put('/questions/note', [AssessmentQuestionController::class, 'saveNote'])->name('questions.note');
    // ตัวเลือกคะแนน (dropdown) ของคอลัมน์ชนิด Input
    Route::put('/questions/scale', [AssessmentQuestionController::class, 'saveScale'])->name('questions.scale');
    Route::put('/questions/{question}', [AssessmentQuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [AssessmentQuestionController::class, 'destroy'])->name('questions.delete');

    // แท็บผลลัพธ์ — ศูนย์จัดการรวม: ตาราง + Template นำเข้า + Export + แก้ผู้ประเมิน/คะแนน inline
    Route::get('/results', [AssessmentScoreController::class, 'resultsPage'])->name('results.index');
    Route::get('/results/filter-options', [AssessmentScoreController::class, 'resultFilterOptions'])->name('results.filter.options');
    Route::get('/results/download', [AssessmentScoreController::class, 'resultsDownload'])->name('results.download');
    // กลุ่ม 1 — Template นำเข้าโดยรวม (ไม่มีคอลัมน์ผลลัพธ์) กรอกแล้ว import กลับผ่าน scores.import
    Route::get('/results/template', [AssessmentScoreController::class, 'resultsTemplateDownload'])->name('results.template');
    // แถวสดหลังแก้ผู้ประเมิน — คืน HTML ของ <tr> เดียว (ปลดล็อกช่องคะแนนโดยไม่ reload)
    Route::get('/results/row', [AssessmentScoreController::class, 'resultRowHtml'])->name('results.row.html');

    // การประเมินตัวเอง — Admin/HR จัดการรายชื่อและพนักงานที่ถูกเลือกเข้าฟอร์มของตนเอง
    Route::get('/self', [AssessmentSelfController::class, 'index'])->name('self.index');
    Route::get('/self/form', [AssessmentSelfController::class, 'form'])->name('self.form');
    // แท็บผลลัพธ์ (self) — Export ผลลัพธ์การประเมินตัวเองของรอบที่เปิดอยู่
    Route::get('/self/results/download', [AssessmentSelfController::class, 'resultsDownload'])->name('self.results.download');
    Route::put('/self/form', [AssessmentSelfController::class, 'saveForm'])->name('self.form.save');
    Route::put('/self/participants', [AssessmentSelfController::class, 'saveParticipants'])->name('self.participants.save');
    Route::put('/self/level', [AssessmentSelfController::class, 'setLevel'])->name('self.level.save');
    // ลบระดับ (ปุ่ม ✕ บนการ์ด 1.1) — ระดับที่สูงกว่าเลื่อนลงมาแทน
    Route::delete('/self/level', [AssessmentSelfController::class, 'deleteLevel'])->name('self.level.delete');
    // บันทึกชุดคำถาม+ตัวเลือกของระดับหนึ่งทีเดียว (หน้า admin แบบใหม่)
    Route::put('/self/questions/set', [AssessmentSelfController::class, 'saveQuestionSet'])->name('self.questions.set');
    Route::post('/self/questions', [AssessmentSelfController::class, 'storeQuestion'])->name('self.questions.store');
    Route::put('/self/questions/{question}', [AssessmentSelfController::class, 'updateQuestion'])->name('self.questions.update');
    Route::delete('/self/questions/{question}', [AssessmentSelfController::class, 'deleteQuestion'])->name('self.questions.delete');
    Route::post('/self/questions/{question}/choices', [AssessmentSelfController::class, 'storeChoice'])->name('self.choices.store');
    Route::put('/self/choices/{choice}', [AssessmentSelfController::class, 'updateChoice'])->name('self.choices.update');
    Route::delete('/self/choices/{choice}', [AssessmentSelfController::class, 'deleteChoice'])->name('self.choices.delete');

    // จัดการคะแนน (admin + HR) — ระดับ, คอลัมน์ global, Template, import, แก้คะแนน
    Route::prefix('scores')->name('scores.')->group(function () {
        Route::get('/', [AssessmentScoreController::class, 'index'])->name('index');
        // ตัวเลือกตัวกรองรายคอลัมน์ (funnel) — กรองข้ามทุกหน้า (ตาราง 1.2 + 4.1)
        Route::get('/filter-options', [AssessmentScoreController::class, 'scoresFilterOptions'])->name('filter.options');
        Route::get('/download', [AssessmentScoreController::class, 'downloadForm'])->name('download');
        Route::get('/score-template', [AssessmentScoreController::class, 'downloadScoreTemplate'])->name('score-template');
        Route::post('/import', [AssessmentScoreController::class, 'import'])->name('import');
        // หัวข้อ 2 จัดการคอลัมน์ — นำเข้าไฟล์คอลัมน์ (อ่านเฉพาะหัวแถว 1–2)
        Route::post('/import-columns', [AssessmentScoreController::class, 'importColumns'])->name('import.columns');

        // ระดับตำแหน่ง (0..N — เพิ่ม/ลบระดับเกิน 5 ได้จากการ์ด 1.1)
        Route::put('/level', [AssessmentScoreController::class, 'setLevel'])->name('level');
        Route::delete('/level', [AssessmentScoreController::class, 'deleteLevel'])->name('level.delete');
        // หัวข้อ 3.1 สัดส่วนของระดับ (ระดับ × หัวข้อหลัก)
        Route::put('/level-prop', [AssessmentScoreController::class, 'saveLevelProp'])->name('level.prop');

        // แก้ลำดับชั้น inline (สเปรดชีตเดียว)
        Route::put('/hierarchy', [AssessmentScoreController::class, 'saveHierarchy'])->name('hierarchy.save');
        // ล้างลำดับชั้น (คนประเมิน) ทั้งหมด
        Route::delete('/hierarchy', [AssessmentScoreController::class, 'clearHierarchy'])->name('hierarchy.clear');

        // คอลัมน์คะแนน (global) — กล่องใหญ่ + คอลัมน์ย่อย
        Route::post('/boxes', [AssessmentScoreController::class, 'storeBox'])->name('boxes.store');
        Route::put('/boxes/{box}', [AssessmentScoreController::class, 'updateBox'])->name('boxes.update');
        Route::delete('/boxes/{box}', [AssessmentScoreController::class, 'deleteBox'])->name('boxes.delete');
        Route::put('/value', [AssessmentScoreController::class, 'updateValue'])->name('value');
        // หัวข้อ 4 — refresh ผลลัพธ์ 1 คนหลังแก้คะแนน inline
        Route::get('/result-row', [AssessmentScoreController::class, 'resultRow'])->name('result.row');
        Route::get('/result-all', [AssessmentScoreController::class, 'resultAll'])->name('result.all');
    });

    // ตั้งค่าระบบ (เฉพาะ admin) — มอบ role HR + ตำแหน่งที่เข้าระบบได้
    Route::middleware('insight.admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AssessmentSettingController::class, 'index'])->name('index');
        Route::get('/users', [AssessmentSettingController::class, 'searchUsers'])->name('users');
        Route::post('/members', [AssessmentSettingController::class, 'addMember'])->name('members.add');
        Route::delete('/members/{member}', [AssessmentSettingController::class, 'removeMember'])->name('members.remove');
        Route::put('/positions', [AssessmentSettingController::class, 'savePositions'])->name('positions');
    });
});
