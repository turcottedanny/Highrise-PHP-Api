<?php

namespace Highrise;

use Highrise\Resources\HighrisePerson;
use Highrise\Resources\HighriseCompany;
use Highrise\Resources\HighriseUser;
use Highrise\Resources\HighriseTask;
use Highrise\Resources\HighriseEmail;
use Highrise\Resources\HighriseNote;
use Highrise\Resources\HighriseTag;

/*
 * http://developer.37signals.com/highrise/people
 *
 * TODO LIST:
 * Add Tasks support
 * Get comments for Notes / Emails
 * findPeopleByTagName
 * Get Company Name, etc proxy
 * Convenience methods for saving Notes $person->saveNotes() to check if notes were modified, etc.
 * Add Tags to Person
 */
class HighriseAPI
{
    // <editor-fold desc="HTTP phrases" defaultstate="collapsed">
    /**
     * Associative array of HTTP status code / reason phrase.
     *
     * @var  array
     * @link http://tools.ietf.org/html/rfc2616#section-10
     */
    protected $phrases = array(
        // 1xx: Informational - Request received, continuing process
        100 => 'Continue',
        101 => 'Switching Protocols',
        // 2xx: Success - The action was successfully received, understood and
        // accepted
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // 3xx: Redirection - Further action must be taken in order to complete
        // the request
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        // 4xx: Client Error - The request contains bad syntax or cannot be
        // fulfilled
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // 5xx: Server Error - The server failed to fulfill an apparently
        // valid request
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',
    );
    // </editor-fold>

    public $account;
    public $token;
    public $userAgent = 'Libcast Highrise SDK (https://github.com/libcast/Highrise-PHP-Api)';
    protected $curl;
    public $debug;

    public function __construct()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
            'Accept: application/xml',
            'Content-Type: application/xml',
            'User-Agent: '.$this->userAgent,
        ));
        // curl_setopt($curl,CURLOPT_POST,true);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
    }

    public function setAccount($account)
    {
        $this->account = $account;
    }

    public function setToken($token)
    {
        $this->token = $token;
        curl_setopt($this->curl, CURLOPT_USERPWD, $this->token . ':x');
    }

    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
            'User-Agent: '.$this->userAgent,
        ));
    }

    public function postDataWithVerb($path, $request_body, $verb = "POST")
    {
        $this->curl = curl_init();

        $url = "https://" . $this->account . ".highrisehq.com" . $path;

        if ($this->debug) {
            print "postDataWithVerb $verb $url ============================\n";
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request_body);

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
        curl_setopt($this->curl, CURLOPT_USERPWD, $this->token . ':x');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, true);

        if ($verb != "POST") {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $verb);
        } else {
            curl_setopt($this->curl, CURLOPT_POST, true);
        }

        $ret = curl_exec($this->curl);

        // Save the full response with headers
        $this->lastResponse = $ret;

        // Return only the body
        $ret = substr($ret, curl_getinfo($this->curl, CURLINFO_HEADER_SIZE));

        if ($this->debug == true) {
            print "Begin Request Body ============================\n" . $request_body . "End Request Body ==============================\n";
        }

        curl_setopt($this->curl, CURLOPT_HTTPGET, true);

        return $ret;
    }

    public function getURL($path)
    {
        $this->curl = curl_init();

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
        curl_setopt($this->curl, CURLOPT_USERPWD, $this->token . ':x');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, true);

        $url = "https://" . $this->account . ".highrisehq.com" . $path;

        curl_setopt($this->curl, CURLOPT_URL, $url);
        $response = curl_exec($this->curl);

        // Save the full response with headers
        $this->lastResponse = $response;

        // Return only the body
        $response = substr($response, curl_getinfo($this->curl, CURLINFO_HEADER_SIZE));

        if ($this->debug == true) {
            print "Response: =============\n" . $response . "============\n";
        }

        return $response;
    }

    protected function getLastReturnStatus()
    {
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    /**
     *
     * @param  string          $statusLine
     * @return array
     * @throws ServerException
     */
    private function getLastReasonPhrase()
    {
        $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $headers = substr($this->lastResponse, 0, $header_size);
        $statusLine = strtok($headers, "\r\n");
        if (!preg_match('!^HTTP/(\d\.\d) (\d{3})(?: (.+))?!', $statusLine, $m)) {
            throw new \Exception("Malformed response status line: {$statusLine}");
        }

        $code = intval($m[2]);

        return !empty($m[3]) ? trim($m[3]) : (isset($this->phrases[$code]) ? $this->phrases[$code] : null);
    }

    protected function getXMLObjectForUrl($url)
    {
        $xml = $this->getURL($url);
        $xml_object = simplexml_load_string($xml);

        return $xml_object;
    }

    public function checkForErrors($type, $expected_status_codes = 200)
    {
        if (!is_array($expected_status_codes))
            $expected_status_codes = array($expected_status_codes);

        if (!in_array($this->getLastReturnStatus(), $expected_status_codes)) {
            switch ($this->getLastReturnStatus()) {
                case 404:
                    throw new \Exception("$type not found");
                    break;
                case 403:
                    throw new \Exception("Access denied to $type resource");
                    break;
                case 507:
                    throw new \Exception("Cannot create $type: Insufficient storage in your Highrise Account");
                    break;

                default:
                    throw new \Exception($this->getLastReturnStatus() . ' ' . $this->getLastReasonPhrase() . ': ' . implode(', ', $this->getErrors()));
            }
        }
    }

    /**
     *
     * @return array of error messages
     */
    protected function getErrors()
    {
        $errors = array();
        $body = substr($this->lastResponse, curl_getinfo($this->curl, CURLINFO_HEADER_SIZE));
        $prev = libxml_use_internal_errors(true);
        try {
            $xml = new \SimpleXMLElement($body);
            foreach ($xml as $error) {
                $errors[] = (string) $error;
            }
        } catch (\Exception $e) {
        }

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $errors;
    }

    public function getSubjectFields()
    {
        $sxe = new \SimpleXMLElement($this->getUrl("/subject_fields.xml"));
        $subjects_fields = array();
        foreach ($sxe as $subject => $values) {
            $subjects_fields[(int) $values->id] = (string) $values->label;
        }

        return $subjects_fields;
    }

    /* Users */
    public function findAllUsers()
    {
        $xml = $this->getUrl("/users.xml");
        $this->checkForErrors("User");

        $xml_object = simplexml_load_string($xml);

        $ret = array();
        foreach ($xml_object->user as $xml_user) {
            $user = new HighriseUser();
            $user->loadFromXMLObject($xml_user);
            $ret[] = $user;
        }

        return $ret;
    }

    public function findMe()
    {
        $xml = $this->getUrl("/me.xml");
        $this->checkForErrors("User");

        $xml_obj = simplexml_load_string($xml);
        $user = new HighriseUser();
        $user->loadFromXMLObject($xml_obj);

        return $user;
    }

    /* Tasks */
    public function findCompletedTasks()
    {
        $xml = $this->getUrl("/tasks/completed.xml");
        $this->checkForErrors("Tasks");

        return $this->parseTasks($xml);
    }

    public function findAssignedTasks()
    {
        $xml = $this->getUrl("/tasks/assigned.xml");
        $this->checkForErrors("Tasks");

        return $this->parseTasks($xml);
    }

    public function findUpcomingTasks()
    {
        $xml = $this->getUrl("/tasks/upcoming.xml");
        $this->checkForErrors("Tasks");

        return $this->parseTasks($xml);
    }

    private function parseTasks($xml)
    {
        $xml_object = simplexml_load_string($xml);
        $ret = array();
        foreach ($xml_object->task as $xml_task) {
            $task = new HighriseTask($this);
            $task->loadFromXMLObject($xml_task);
            $ret[] = $task;
        }

        return $ret;
    }

    public function findTaskById($id)
    {
        $xml = $this->getURL("/tasks/$id.xml");
        $this->checkForErrors("Task");
        $task_xml = simplexml_load_string($xml);
        $task = new HighriseTask($this);
        $task->loadFromXMLObject($task_xml);

        return $task;
    }

    /* Notes & Emails */
    public function findEmailById($id)
    {
        $xml = $this->getURL("/emails/$id.xml");
        $this->checkForErrors("Email");
        $email_xml = simplexml_load_string($xml);
        $email = new HighriseEmail($this);
        $email->loadFromXMLObject($email_xml);

        return $email;
    }

    public function findNoteById($id)
    {
        $xml = $this->getURL("/notes/$id.xml");
        $this->checkForErrors("Note");
        $note_xml = simplexml_load_string($xml);
        $note = new HighriseNote($this);
        $note->loadFromXMLObject($note_xml);

        return $note;
    }

    public function findPersonById($id)
    {
        $xml = $this->getURL("/people/$id.xml");

        $this->checkForErrors("Person");

        $xml_object = simplexml_load_string($xml);

        $person = new HighrisePerson($this);
        $person->loadFromXMLObject($xml_object);

        return $person;
    }

    public function findAllTags()
    {
        $xml = $this->getUrl("/tags.xml");
        $this->checkForErrors("Tags");

        $xml_object = simplexml_load_string($xml);
        $ret = array();
        foreach ($xml_object->tag as $tag) {
            $ret[(string) $tag->name] = new HighriseTag((string) $tag->id, (string) $tag->name);
        }

        return $ret;
    }

    public function findAllPeople()
    {
        return $this->parsePeopleListing("/people.xml");
    }

    public function findPeopleByTagName($tag_name)
    {
        $tags = $this->findAllTags();
        foreach ($tags as $tag) {
            if ($tag->name == $tag_name)
                $tag_id = $tag->id;
        }

        if (!isset($tag_id))
            throw new Excepcion("Tag $tag_name not found");

        return $this->findPeopleByTagId($tag_id);
    }

    public function findPeopleByTagId($tag_id)
    {
        $url = "/people.xml?tag_id=" . $tag_id;
        $people = $this->parsePeopleListing($url);

        return $people;
    }

    public function findPeopleByEmail($email)
    {
        return $this->findPeopleBySearchCriteria(array("email" => $email));
    }

    public function findPeopleByTitle($title)
    {
        $url = "/people.xml?title=" . urlencode($title);

        $people = $this->parsePeopleListing($url);

        return $people;
    }

    public function findPeopleByCompanyId($company_id)
    {
        $url = "/companies/" . urlencode($company_id) . "/people.xml";
        $people = $this->parsePeopleListing($url);

        return $people;
    }

    public function findPeopleBySearchTerm($search_term)
    {
        $url = "/people/search.xml?term=" . urlencode($search_term);
        $people = $this->parsePeopleListing($url, 25);

        return $people;
    }

    public function findPeopleBySearchCriteria($search_criteria)
    {
        $url = "/people/search.xml";

        $sep = "?";
        foreach ($search_criteria as $criteria => $value) {
            $url .= $sep . "criteria[" . urlencode($criteria) . "]=" . urlencode($value);
            $sep = "&";
        }

        $people = $this->parsePeopleListing($url, 25);

        return $people;
    }

    public function findPeopleSinceTime($time)
    {
        $url = "/people/search.xml?since=" . urlencode($time);
        $people = $this->parsePeopleListing($url);

        return $people;
    }

    public function parsePeopleListing($url, $paging_results = 500)
    {
        if (strstr($url, "?"))
            $sep = "&";
        else
            $sep = "?";

        $offset = 0;
        $return = array();
        while (true) { // pagination
            $xml_url = $url . $sep . "n=$offset";
            // print $xml_url;
            $xml = $this->getUrl($xml_url);
            $this->checkForErrors("People");
            $xml_object = simplexml_load_string($xml);

            foreach ($xml_object->person as $xml_person) {
                // print_r($xml_person);
                $person = new HighrisePerson($this);
                $person->loadFromXMLObject($xml_person);
                $return[] = $person;
            }

            if (count($xml_object) != $paging_results) {
                break;
            }

            $offset += $paging_results;
        }

        return $return;
    }

    // Companies

    public function findCompanyById($id)
    {
        $xml = $this->getURL("/companies/$id.xml");

        $this->checkForErrors("Company");

        $xml_object = simplexml_load_string($xml);

        $company = new HighriseCompany($this);
        $company->loadFromXMLObject($xml_object);

        return $company;
    }

    public function findCompaniesByName($name)
    {
        $url = "/companies/search.xml?criteria[name]=" . urlencode($name);

        $companies = $this->parseCompaniesListing($url);

        return $companies;
    }

    public function parseCompaniesListing($url, $paging_results = 500)
    {
        if (strstr($url, "?")) {
            $sep = "&";
        } else {
            $sep = "?";
        }

        $offset = 0;
        $return = array();
        while (true) { // pagination
            $xml_url = $url . $sep . "n=$offset";
            $xml = $this->getUrl($xml_url);
            $this->checkForErrors("Company");
            $xml_object = simplexml_load_string($xml);
            foreach ($xml_object->company as $xml_company) {
                $company = new HighriseCompany($this);
                $company->loadFromXMLObject($xml_company);
                $return[] = $company;
            }

            if (count($xml_object) != $paging_results)
                break;

            $offset += $paging_results;
        }

        return $return;
    }
}
