<?php

namespace application\controllers;

require_once __DIR__ . '/../../vendor/autoload.php';
use application\core\Controller;
use application\models\SurveyModel;
use PDO;
class SurveyController extends Controller
{
    protected $pdo;

    public function indexAction()
    {
        $surveyModel = new SurveyModel();

        $surveys = $surveyModel->getAllSurveys();

        $data = [
            'title' => 'Список опросов',
            'surveys' => $surveys
        ];

        $this->view->render('survey/index', $data);
    }

    public function createAction()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $question = $_POST['title'];
            $status = $_POST['status'];
            $count = $_POST['count'];
            $answer = $_POST['answer'];
            $surveyModel = new SurveyModel();
            $surveyModel->createSurvey($question, $status,$count,$answer);

            header('Location: /application/views/layout.php');
            exit();
        }

        $data = [
            'title' => 'Создать опрос'
        ];

        $this->view->render('survey/create', $data);
    }

    public function editAction($id)
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST)) {
                $surveyId = isset($_POST['survey_id']) ? $_POST['survey_id'] : null;
                $question = isset($_POST['question']) ? $_POST['question'] : null;
                $answers = isset($_POST['answers']) ? $_POST['answers'] : null;

                if ($surveyId && $question && $answers) {
                    $surveyModel = new SurveyModel();
                    $surveyModel->updateSurvey($surveyId, $question, $answers);
                } else {
                }

                header('Location: /application/views/layout.php');
                exit();
            } else {

            }
        }
        $surveyId = $id;

        $surveyModel = new SurveyModel();
        $survey = $surveyModel->getSurveyById($surveyId);

        // Подключение файла edit.php напрямую
        require_once 'C:/project/NewProject/application/views/form/edit.php';
    }

    public function deleteAction($id)
    {

        $surveyId = $id;
        $surveyModel = new SurveyModel();
        $surveyModel->deleteSurvey($surveyId);

        header('Location: /application/views/layout.php');
        exit();
    }

    public function getAllSurveysAction($sort = 'date_create')
    {
        try {
            $model = new SurveyModel();
            $db = new PDO('pgsql:host=127.0.0.1;dbname=postgres', 'postgres', '1234');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $user = $_SESSION['user_id'];

            $query = $model->getAllSurveys();

            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user, PDO::PARAM_INT);
            $stmt->execute();

            $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $voteCounts = $model->getVoteCounts(NULL);

            $countVotes = $model->getCountAnwer();

            $processedSurveys = [];
            foreach ($surveys as $survey) {
                $surveyId = $survey['survey_id'];

                $answerId = isset($survey['answer_id']) ? $survey['answer_id'] : null;
                $answer = $survey['answer'];
                if (!isset($processedSurveys[$surveyId])) {
                    $processedSurveys[$surveyId] = [
                        'id' => $surveyId,
                        'title' => $survey['title'],
                        'status' => $survey['status'],
                        'date_create' => $survey['date_create'],
                        'answers' => [],
                    ];
                }
                if ($survey['c_answer_id'] !== NULL && isset($countVotes[0]['count'])) {

                    $param = $countVotes[0]['count'];

                    $processedSurveys[$surveyId]['answers'][] = [
                        'answer_id' => $answerId,
                        'answer' => $answer,
                        'user_id' => $user,
                        'vote_count' => $param,
                        'user_id_for_check' => $countVotes[0]['user_id_for_check'],
                        'survay_id' => $countVotes[0]['survay_id'],
                        'c_answer_id' => $survey['c_answer_id'],
                        'user_id_c' => $survey['user_id_c']
                    ];

                } else {
                    $processedSurveys[$surveyId]['answers'][] = [
                        'answer_id' => $answerId,
                        'answer' => $answer,
                        'user_id' => $user,
                        'vote_count' => 0,
                        'survay_id' => $surveyId,
                        'c_answer_id' => $survey['c_answer_id']
                    ];
                }
            }

            if(isset($_GET['value'])){
            if ($_GET['value'] == 1) {
                uasort($processedSurveys, function ($a, $b) {
                    return $a['status'] <=> $b['status'];
                });
            } elseif ($_GET['value'] == 2) {
                uasort($processedSurveys, function ($a, $b) {
                    return strtotime($a['date_create']) <=> strtotime($b['date_create']);
                });
            } elseif ($_GET['value'] == 3) {
                uasort($processedSurveys, function ($a, $b) {
                    return $a['title'] <=> $b['title'];
                });
            }}

            $data = [
                'surveys' => $processedSurveys
            ];

            ob_start();
            extract($data);
            include(__DIR__ . '/../views/surveyList.php');
            ob_end_flush();

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    public function submitAnswerAction()
    {
        header('Content-Type: application/json');
        $questionId = $_POST['questionId'];
        $answerId = $_POST['answerId'];

        $model = new SurveyModel();
        $user = $_SESSION['user_id'];

        // Вызов метода модели для сохранения ответа
        $success = $model->saveAnswer($questionId, $answerId, $user);

        if ($success) {
            // Получение информации о количестве проголосовавших людей для каждого ответа
            //$voteCounts = $model->getVoteCounts(null);
            $voteCounts = $model->getVoteCounts($questionId);
            $response = array();
            $index = 0;
            foreach($voteCounts as $row) {

                $response[$index]['id'] = $row['id'];
                $response[$index]['vote_count'] = $row['vote_count'];
                $response[$index]['survey_id'] = $row['survey_id'];
                $response[$index]['answer'] = $row['answer'];
                $response[$index]['title'] = $row['title'];
                $response[$index]['status'] = $row['status'];
                $response[$index]['date_create'] = $row['date_create'];
                $index++;
            }
            $response['counts'] = $index;

            echo json_encode($response);
        } else {

            $response = ['success' => false];
            echo json_encode($response);
        }
    }


    public function checkAcl()
    {

        return true;
    }



}