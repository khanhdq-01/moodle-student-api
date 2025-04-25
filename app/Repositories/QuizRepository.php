<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Interfaces\QuizInterface;

class QuizRepository implements QuizInterface
{
    public function startAttempt(int $quizId, int $userId)
    {
        $quiz = DB::table('mdl_quiz')->where('id', $quizId)->first();
        if (!$quiz) return response()->json(['message' => 'Quiz not found'], 404);

        $contextId = DB::table('mdl_context')
            ->where('contextlevel', 50)
            ->where('instanceid', $quiz->course)
            ->value('id');

        $questionUsageId = DB::table('mdl_question_usages')->insertGetId([
            'component' => 'mod_quiz',
            'contextid' => $contextId,
            'preferredbehaviour' => 'deferredfeedback',
        ]);

        $slots = DB::table('mdl_quiz_slots')
            ->where('quizid', $quizId)
            ->orderBy('slot')
            ->get();

        $layout = [];

        foreach ($slots as $slot) {
            $questionAttemptId = DB::table('mdl_question_attempts')->insertGetId([
                'questionusageid' => $questionUsageId,
                'questionid'      => $slot->questionid,
                'slot'            => $slot->slot,
                'maxmark'         => $slot->maxmark,
                'minfraction'     => 0,
                'maxfraction'     => 1,
                'flagged'         => 0,
                'questionsummary' => '',
                'rightanswer'     => '',
                'responsesummary' => '',
                'timemodified'    => time(),
            ]);

            DB::table('mdl_question_attempt_steps')->insert([
                'questionattemptid' => $questionAttemptId,
                'sequencenumber'    => 0,
                'state'             => 'todo',
                'timecreated'       => time(),
                'userid'            => $userId,
            ]);

            $layout[] = $slot->slot;
        }

        $latestAttempt = DB::table('mdl_quiz_attempts')
            ->where('quiz', $quizId)
            ->where('userid', $userId)
            ->max('attempt');

        $nextAttemptNumber = $latestAttempt ? $latestAttempt + 1 : 1;

        $attemptId = DB::table('mdl_quiz_attempts')->insertGetId([
            'quiz'        => $quizId,
            'userid'      => $userId,
            'attempt'     => $nextAttemptNumber,
            'uniqueid'    => $questionUsageId,
            'timestart'   => time(),
            'timemodified'=> time(),
            'state'       => 'inprogress',
            'layout'      => implode(',', $layout) . ',0',
        ]);

        return ['attempt_id' => $attemptId];
    }

    public function submitAnswer(int $quizId, int $userId, array $data)
    {
        $attemptId  = $data['attempt_id'];
        $questionId = $data['question_id'];
        $answerIds  = $data['answer_ids'];

        $attempt = DB::table('mdl_quiz_attempts')
            ->where('id', $attemptId)
            ->where('userid', $userId)
            ->first();

        if (!$attempt) return response()->json(['message' => 'Attempt not found'], 404);

        $questionUsageId = $attempt->uniqueid;

        $questionAttempt = DB::table('mdl_question_attempts')
            ->where('questionusageid', $questionUsageId)
            ->where('questionid', $questionId)
            ->first();

        if (!$questionAttempt) return response()->json(['message' => 'Question attempt not found'], 404);

        $question = DB::table('mdl_question')->where('id', $questionId)->first();
        if (!$question) return response()->json(['message' => 'Question not found'], 404);

        if (!in_array($question->qtype, ['multichoice', 'truefalse'])) {
            return response()->json(['message' => 'Unsupported question type: ' . $question->qtype], 400);
        }

        $answers = DB::table('mdl_question_answers')
            ->where('question', $question->id)
            ->orderBy('id')
            ->get();

        $answerIndexMap = [];
        foreach ($answers as $index => $ans) {
            $answerIndexMap[$ans->id] = $index;
        }

        $stepId = DB::table('mdl_question_attempt_steps')->insertGetId([
            'questionattemptid' => $questionAttempt->id,
            'sequencenumber'    => 1,
            'state'             => 'complete',
            'fraction'          => null,
            'timecreated'       => time(),
            'userid'            => $userId,
        ]);

        $stepData = [];
        foreach ($answerIds as $answerId) {
            if (!isset($answerIndexMap[$answerId])) {
                return response()->json(['message' => 'Invalid answer_id: ' . $answerId], 400);
            }

            $index = $answerIndexMap[$answerId];
            $stepData[] = [
                'attemptstepid' => $stepId,
                'name'  => '_choice' . $index,
                'value' => '1',
            ];
        }

        DB::table('mdl_question_attempt_step_data')->insert($stepData);

        return ['message' => 'Answer submitted'];
    }

public function finishAttempt(int $quizId, int $userId, int $attemptId)
{
    $attempt = DB::table('mdl_quiz_attempts')
        ->where('id', $attemptId)
        ->where('userid', $userId)
        ->first();

    if (!$attempt) {
        return response()->json(['message' => 'Attempt not found'], 404);
    }

    $questionAttempts = DB::table('mdl_question_attempts')
        ->where('questionusageid', $attempt->uniqueid)
        ->get();

    $totalScore = 0;
    $details = [];

    foreach ($questionAttempts as $qa) {
        $question = DB::table('mdl_question')->where('id', $qa->questionid)->first();
        if (!$question) continue;

        $step = DB::table('mdl_question_attempt_steps')
            ->where('questionattemptid', $qa->id)
            ->where('state', 'complete')
            ->orderByDesc('id')
            ->first();

        if (!$step) continue;

        $stepData = DB::table('mdl_question_attempt_step_data')
            ->where('attemptstepid', $step->id)
            ->get()
            ->keyBy('name');

        $userAnswers = [];
        $correctAnswers = [];
        $fraction = 0;

        switch ($question->qtype) {
            case 'multichoice':
            case 'truefalse':
                $answers = DB::table('mdl_question_answers')
                    ->where('question', $question->id)
                    ->orderBy('id')
                    ->get();

                $indexToAnswerId = [];
                foreach ($answers as $index => $ans) {
                    $indexToAnswerId[$index] = $ans->id;
                    if ($ans->fraction > 0) {
                        $correctAnswers[] = $ans->id;
                    }
                }

                foreach ($stepData as $name => $value) {
                    if (preg_match('/_choice(\d+)/', $name, $matches)) {
                        $index = intval($matches[1]);
                        if (isset($indexToAnswerId[$index])) {
                            $userAnswers[] = $indexToAnswerId[$index];
                        }
                    }
                }

                $isSingle = count($correctAnswers) === 1;
                if ($isSingle) {
                    $fraction = in_array($correctAnswers[0], $userAnswers) ? 1 : 0;
                } else {
                    $correctCount = count($correctAnswers);
                    $userCorrect = count(array_intersect($userAnswers, $correctAnswers));
                    $userWrong = count(array_diff($userAnswers, $correctAnswers));
                    $fraction = ($userWrong === 0 && $correctCount > 0) ? ($userCorrect / $correctCount) : 0;
                }

                break;

            case 'shortanswer':
                $userAnswer = $stepData['_answer']->value ?? '';
                $userAnswers[] = $userAnswer;

                $answers = DB::table('mdl_question_answers')
                    ->where('question', $question->id)
                    ->where('fraction', '>', 0)
                    ->pluck('answer');

                foreach ($answers as $correct) {
                    if (strcasecmp(trim($userAnswer), trim($correct)) === 0) {
                        $fraction = 1;
                        break;
                    }
                }

                $correctAnswers = $answers->toArray();
                break;

            case 'numerical':
                $userAnswer = $stepData['_answer']->value ?? null;
                $userAnswers[] = $userAnswer;

                $answer = DB::table('mdl_question_answers')
                    ->where('question', $question->id)
                    ->where('fraction', '>', 0)
                    ->first();

                if ($answer) {
                    $correctValue = floatval($answer->answer);
                    $tolerance = DB::table('mdl_question_numerical')
                        ->where('question', $question->id)
                        ->value('tolerance') ?? 0;

                    if (is_numeric($userAnswer)) {
                        $userNum = floatval($userAnswer);
                        if (abs($userNum - $correctValue) <= $tolerance) {
                            $fraction = 1;
                        }
                    }

                    $correctAnswers[] = $correctValue;
                }

                break;

            default:
                // Unsupported question type
                continue 2;
        }

        DB::table('mdl_question_attempt_steps')
            ->where('id', $step->id)
            ->update(['fraction' => $fraction]);

        $score = $fraction * $qa->maxmark;
        $totalScore += $score;

        $details[] = [
            'question_id'     => $question->id,
            'qtype'           => $question->qtype,
            'score'           => round($score, 2),
            'correct_answers' => $correctAnswers,
            'user_answers'    => $userAnswers,
        ];
    }

    DB::table('mdl_quiz_attempts')
        ->where('id', $attempt->id)
        ->update([
            'timefinish' => time(),
            'state'      => 'finished',
            'sumgrades'  => $totalScore,
        ]);

    return [
        'message'     => 'Attempt finished',
        'total_score' => round($totalScore, 2),
        'details'     => $details,
    ];
}




    public function getResult(int $quizId, int $userId)
    {
        $attempt = DB::table('mdl_quiz_attempts')
            ->where('quiz', $quizId)
            ->where('userid', $userId)
            ->orderByDesc('id')
            ->first();

        if (!$attempt) {
            return response()->json(['message' => 'No attempt found'], 404);
        }

        return [
            'attempt_id'  => $attempt->id,
            'state'       => $attempt->state,
            'started_at'  => date('Y-m-d H:i:s', $attempt->timestart),
            'finished_at' => $attempt->timefinish ? date('Y-m-d H:i:s', $attempt->timefinish) : null,
        ];
    }
}
