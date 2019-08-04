<?php

namespace App\Controllers;

use App\DBManagers\DBLanguage;
use App\Managers\Translation;
use App\User\User;
use App\Utils;
use Psr\Http\Message\ResponseInterface;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Slim\Router;
use Slim\Views\Twig;

/**
 * Class LanguageController
 *
 * @property Twig view
 * @property \dbaccess database
 * @property Translation translation
 * @property User user
 * @property Messages flash
 * @property Router router
 * @package App\Controllers
 */
class LanguageController extends Controller
{
    private $responseData;

    public function __construct($container)
    {
        $this->responseData['pageTitle'] = $container->translation->getTranslation('Translations', 'translationsTitle');
        parent::__construct($container);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function languagePage($request, $response)
    {
        return $this->view->render($response, 'content/language/language_list.twig', $this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getLanguages($request, $response)
    {
        $postData = $request->getParams();

        $data = array();

        $search_condition = '';
        if (array_key_exists('search', $postData)) {
            if ($postData['search']['value'] != '') {
                $search = strtolower($this->database->escape($postData['search']['value']));
                $search_condition = " AND (lower(languages) || lower(language_code)) LIKE '%$search%' ";
            }
        }

        $pagination['start'] = 0;
        if (array_key_exists('start', $postData)) {
            $pagination['start'] = (int)$postData['start'];
        }
        $pagination['length'] = 0;
        if (array_key_exists('length', $postData)) {
            $pagination['length'] = (int)$postData['length'];
        }

        $pagination_string = " LIMIT " . $this->database->escape($pagination['length'] . " OFFSET " . $this->database->escape($pagination['start']));

        // Sort
        $order = array();
        if (array_key_exists('order', $postData)) {
            $order['column'] = $postData['order'][0]['column'];

            if (array_key_exists('dir', $postData['order'][0])) {
                $order['dir'] = strtoupper($this->database->escape($postData['order'][0]['dir']));
            } else {
                $order['dir'] = 'ASC';
            }
        } else {
            $order['column'] = 0;
            $order['dir'] = 'ASC';
        }

        switch ($order['column']) {
            case 0:
                $order_condition = " ORDER BY languages " . $order['dir'];
                break;
            case 1:
                $order_condition = " ORDER BY language_code " . $order['dir'];
                break;
            case 2:
                $order_condition = " ";
                break;
            default:
                $order_condition = " ORDER BY languages " . $order['dir'];
                break;
        }

        $query = "SELECT * FROM " . DBLanguage::$DB_NAME . " WHERE TRUE" . $search_condition;

        $res_full = $this->database->execute($query);

        if ($this->database->error() || $this->database->rows() <= 0) {
            $res_full = array();
        }

        $query_pagination = $query . $search_condition . $order_condition . $pagination_string;
        $res_pagination = $this->database->execute($query_pagination);

        if ($this->database->error() || $this->database->rows() <= 0) {
            $res_pagination = array();
        }

        $data['data'] = $res_pagination;
        $data['recordsFiltered'] = count($res_full);
        $data['recordsTotal'] = count($res_pagination);

        return $response->withJson($data);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteLanguage($request, $response)
    {

        $postData = $request->getParams();
        $id = $postData['id'];

        if (is_numeric($id)) {
            $obj = DBLanguage::loadById($id, $this->database);
            if (!$obj) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'invalidID')]);
            }
        } else {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'invalidID')]);
        }

        if ($obj->is_system == LANGUAGE_SYSTEM_YES) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Translations', 'translationErrorSystem')]);
        }

        if ($obj->delete()) {
            return $response->withJson(['st' => 1]);
        }

        return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function addLanguagePage($request, $response)
    {
        return $this->view->render($response, 'content/language/language_add.twig', $this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addLanguage($request, $response)
    {

        $postData = $request->getParams();

        if (array_key_exists('languages', $postData) && Utils::validateLanguage($postData['languages'])) {
            $language = $postData['languages'];
        } else {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'invalidID')]);
        }

        if (array_key_exists('language_code', $postData) && Utils::validateLanguage($postData['language_code'], 2, 3)) {
            $code = $postData['language_code'];
        } else {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Translations', 'translationErrorCode')]);
        }

        $query = "SELECT * FROM " . DBLanguage::$DB_NAME . " WHERE lower(languages) = lower('$language') OR lower(language_code) = lower('$code')";

        $this->database->execute($query);

        if ($this->database->error()) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
        }

        if ($this->database->rows() > 0) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Translations', 'translationDuplicate')]);
        }

        $uploadedFiles = $request->getUploadedFiles();
        if (!array_key_exists('language_flag', $uploadedFiles)) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
        }

        /** @var UploadedFile $file */
        $file = $uploadedFiles['language_flag'];
        $fileInfo = pathinfo($file->getClientFilename());
        if (!in_array($file->getClientMediaType(), Utils::returnImageFileTypes())) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Translations', 'translationsFlagErrorFileType')]);
        }

        $newLanguage = new DBLanguage($this->database);
        $newLanguage->languages = $language;
        $newLanguage->language_code = $code;
        $newLanguage->creator_id = $this->user->getId();
        $newLanguage->filename = strtolower($code) . '.ini';
        $newLanguage->flag_temp_file = $file->file;
        $newLanguage->language_flag = strtolower($code) . '.' . $fileInfo['extension'];

        $id = $newLanguage->saveAsNew();
        if (is_numeric($id)) {
            return $response->withJson(['st' => 1]);
        }

        return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     */
    public function editLanguagePage($request, $response, $args)
    {
        $lang = DBLanguage::loadById($args['id'], $this->database);
        if (!$lang) {

            $this->flash->addMessage('warning', $this->translation->getTranslation('System', 'invalidID'));
            return $response->withRedirect($this->router->pathFor('languages'));
        }
        $this->responseData['lang'] = $lang;
        return $this->view->render($response, 'content/language/language_edit.twig', $this->responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function editLanguage($request, $response)
    {
        $postData = $request->getParams();
        if (array_key_exists('id', $postData) && is_numeric($postData['id'])) {
            $obj = DBLanguage::loadById($postData['id'], $this->database);

            if (!$obj) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'invalidID')]);
            }
        } else {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'invalidID')]);
        }

        if (!array_key_exists('data', $postData) || !is_array($postData['data'])) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
        }

        if (array_key_exists('is_active', $postData) && ($postData['is_active'] == 'true' || $postData['is_active'] == 'false')) {
            if ($postData['is_active'] == 'true') {
                $obj->is_active = LANGUAGE_ACTIVE_YES;
            } else {
                $obj->is_active = LANGUAGE_ACTIVE_NO;
            }
        } else {
            $obj->is_active = LANGUAGE_ACTIVE_NO;
        }

        $uploadedFiles = $request->getUploadedFiles();
        if (!array_key_exists('language_flag', $uploadedFiles)) {
            return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
        }

        /** @var UploadedFile $file */
        $file = $uploadedFiles['language_flag'];

        if ($file->getClientFilename()) {
            if (!in_array($file->getClientMediaType(), Utils::returnImageFileTypes())) {
                return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('Translations', 'translationsFlagErrorFileType')]);
            }
            $fileInfo = pathinfo($file->getClientFilename());

            $languageList = $this->translation->getLanguageList();
            $fileName = $languageList[array_search($postData['id'], array_column($languageList, 'id'))]['code'];

            $obj->flag_temp_file = $file->file;
            $obj->language_flag = strtolower($fileName) . '.' . $fileInfo['extension'];
        }

        $obj->data = $postData['data'];

        $obj->updater_id = $_SESSION['person_id'];

        if ($obj->save()) {
            return $response->withJson(['st' => 1]);
        }

        return $response->withJson(['st' => 3, 'msg' => $this->translation->getTranslation('System', 'systemError')]);
    }
}