<?php

namespace Legacy\Api;
use Legacy\Api\Module;
use Legacy\Api\User;
use Legacy\Api\Course;
use Legacy\Api\Question;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Test
{
    public static function GetUserTests() {

        $testsInfo = Course::GetCoursesTestsInfo();

        $arCoursesTestResults = [];
        foreach ($testsInfo['courses_tests_info'] as $course_test){
            $result = self::GetDoneTestBefore($course_test['course_id'],$course_test['test_id']);
            if($result){
                $arCoursesTestResults[] = [
                    'name'=>$course_test['name'],
                    'course_id'=> $course_test['course_id'],
                    'test_id'=>$course_test['test_id'],
                    'results' => [
                        'correct_answers'=>$result['correct_answers'],
                        'questions_count'=>$result['questions_count']
                        ]
                ];
            } else{
                $arCoursesTestResults[] = [
                    'name'=>$course_test['name'],
                    'course_id'=> $course_test['course_id'],
                    'test_id'=>$course_test['test_id'],
                    'results' => null
                ];
            }
        }

        if(count($testsInfo['tests_ids'])){
            $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Tests,
                'ACTIVE'=>'Y', 'ID' => $testsInfo['tests_ids']
            );

            $arSelect = [
                'ID',
                'NAME'
            ];

            $res = \CIBlockElement::GetList('ASC', $arFilter, false, false, $arSelect);

            $arTests = [];

            while($item = $res->Fetch()){
                foreach($item as $key => $value){
                    if(strripos($key,'VALUE_ID')){
                        unset($item[$key]);
                        continue;
                    }
                    if(strripos($key,'PROPERTY') !== false){
                        $old_key = $key;
                        $key = str_replace(['PROPERTY_','_VALUE','~'], '', $key);
                        $item[$key] = $value;
                        unset($item[$old_key]);
                    }
                }

                $arTests[] = [
                    'id' => $item['ID'],
                    'name' => $item['NAME']
                ];
            }


            return Helper::GetResponseApi(200, [
                'tests' => $arTests,
                'courses_tests_info' => $arCoursesTestResults
            ]);
        }
        else {
            return Helper::GetResponseApi(200, [
                'tests' => [],
                'courses_tests_info' => []
            ]);
        }

    }

    public static function GetTest($arRequest) {
        $testID = $arRequest['test_id'];
        $courseID = $arRequest['course_id'];

        return Helper::GetResponseApi(200, [
            'questions' => Question::GetQuestionsInfo($testID),
            'test_info' => self::GetTestInfo($testID),
            'course_info' => Course::GetCourseInfo($courseID)
        ]);
    }

    public static function GetTestInfo($testID) {

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Tests,
            'ACTIVE'=>'Y', 'ID' => $testID
        );

        $arSelect = [
            'ID',
            'NAME'
        ];

        $res = \CIBlockElement::GetList('ASC', $arFilter, false, false, $arSelect);

        $arTests = [];

        while($item = $res->Fetch()){
            foreach($item as $key => $value){
                if(strripos($key,'VALUE_ID')){
                    unset($item[$key]);
                    continue;
                }
                if(strripos($key,'PROPERTY') !== false){
                    $old_key = $key;
                    $key = str_replace(['PROPERTY_','_VALUE','~'], '', $key);
                    $item[$key] = $value;
                    unset($item[$old_key]);
                }
            }

            $arTests[] = [
                'id' => $item['ID'],
                'name' => $item['NAME']
            ];
        }


        if(isset($arTests[0])) {
            return $arTests[0];
        } else{
            return null;
        }
    }


    public static function GetTestResultInfo($testID,$userAnswers) {
        $testAnswers = Question::GetQuestionsAnswersInfo($testID);

        $correctAnswersCount = 0;
        foreach ($testAnswers as $id => $questionAnswers){
            if(isset($userAnswers[$id])){
               if(count(array_intersect($userAnswers[$id], $questionAnswers)) === count($questionAnswers)
                   && count(array_diff($userAnswers[$id], $questionAnswers)) === 0
                   && count(array_diff($questionAnswers, $userAnswers[$id])) === 0 ){
                   $correctAnswersCount++;
               };
            }
        }

        return [
            'correct_answers' => $correctAnswersCount,
            'questions_count' => count($testAnswers),
        ];
    }

    //перенести в резалт
    public static function GetDoneTestBefore($courseID) {
        global $USER;

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Result_tests,
            'ACTIVE'=>'Y',
            'USER'=>$USER->GetID(),
            'PROPERTY_COURSE' => $courseID
        );

        $arSelect = [
            'ID',
            'PROPERTY_RESULT',
            'PROPERTY_TEST',
            'PROPERTY_QUESTIONS_COUNT'
        ];

        $res = \CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false, false, $arSelect);
        $arResult = [];

        while($item = $res->Fetch()){
            foreach($item as $key => $value){
                if(strripos($key,'VALUE_ID')){
                    unset($item[$key]);
                    continue;
                }
                if(strripos($key,'PROPERTY') !== false){
                    $old_key = $key;
                    $key = str_replace(['PROPERTY_','_VALUE','~'], '', $key);
                    $item[$key] = $value;
                    unset($item[$old_key]);
                }
            }

            $arResult[] =   [
                'id' => $item['ID'],
                'test_id' => $item['TEST'],
                'correct_answers' =>$item['RESULT'],
                'questions_count' =>$item['QUESTIONS_COUNT']
            ];
        }


         if(count($arResult) > 0){
             return $arResult[0];
         } else {
             return false;
         }
    }

    public static function AddDoneTest($arRequest){
        global $USER;

        $courseID = $arRequest['course_id'];
        $testID = $arRequest['test_id'];
        $userAnswers = $arRequest['test_answers'];

        $testResult = self::GetTestResultInfo($testID,$userAnswers);


        $arLoadProperties = Array(
            'NAME' => 'Элемент',
            'IBLOCK_ID'=> \Legacy\Config::Result_tests,
            'ACTIVE'=>'Y',
            'PROPERTY_VALUES'=>[
                'COURSE'=>$courseID,
                'TEST'=>$testID,
                'USER'=>$USER->GetID(),
                'RESULT'=>$testResult['correct_answers'],
                'QUESTIONS_COUNT'=>$testResult['questions_count'],
            ]
        );

        if($id = self::GetDoneTestBefore($courseID)['id']){
            $el = new \CIBlockElement;
            if($res = $el->Update($id, $arLoadProperties)){
                return Helper::GetResponseApi(200, [
                    'correct_answers' => $testResult['correct_answers'],
                    'questions_count' => $testResult['questions_count'],
                ]);
            } else{
                return Helper::GetResponseApi(404, [],
                    'Ошибка. Повторите попытку позднее.' . $res->LAST_ERROR);
            }
        } else {
            $el = new \CIBlockElement;
            if($res = $el->Add($arLoadProperties)){
                return Helper::GetResponseApi(200, [
                    'correct_answers' => $testResult['correct_answers'],
                    'questions_count' => $testResult['questions_count'],
                ]);
            } else{
                return Helper::GetResponseApi(404, [],
                    'Ошибка. Повторите попытку позднее.' . $res->LAST_ERROR);
            }
        }
    }



}