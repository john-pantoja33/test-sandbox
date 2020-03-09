<?php
namespace Tasker;

use Exception;
use Tasker\Database;

class Api {
  protected $endpoint = null;
  protected $database = null;

  protected $uri = null;
  protected $query = null;
  protected $data = null;
  protected $method = null;

  protected $headers = null;
  protected $status = null;
  protected $response = null;

  public function __construct() {
    $this->endpoint = "/tasks/";
    $this->database = new Database();
    $this->database->connect();

    $this->headers = array();
  }

  public function request() {
    $this->resetHeaders();
    $this->parseURI();
    $this->parseMethod();
    $this->parseQuery();
    $this->parseData();
  }

  public function process() {
    try {
      switch (true) {
        case $this->method === "get" && is_string($this->uri) && $this->uri === "":
        case $this->method === "get" && is_numeric($this->uri) === false:
        case $this->method === "get" && is_numeric($this->uri) && (int) $this->uri <= 0:
          $this->all();
          break;
        case $this->method === "get" && is_numeric($this->uri) && (int) $this->uri > 0:
          $this->fetch();
          break;
        case $this->method === "post":
          $this->create();
          break;
        case $this->method === "put":
          $this->update();
          break;
        case $this->method === "delete":
          $this->delete();
          break;
        default:
          throw new Exception("Unknown method.", 412);
          break;
      }
    } catch (Exception $err) {
      $this->setStatus($err->getCode());
      $this->setResponse(array(
        "error" => true,
        "message" => $err->getMessage(),
      ));
    }
  }

  public function response() {
    $this->sendStatus();
    $this->sendHeaders();
    $this->sendResponse();
  }

  protected function setStatus($status) {
    if (is_numeric($status) && ((int) $status >= 100 && (int) $status <= 505)) {
      $this->status = $status;
    } else {
      $this->status = 400;
    }

    return $status;
  }

  protected function setHeader($header) {
    if (is_string($header) && $header !== "") {
      $this->headers[] = $header;
    }
  }

  protected function setResponse($response) {
    if (is_null($response) === false) {
      $this->response = json_encode($response);
      $this->setHeader("Content-Length: " . strlen($this->response));
    } else {
      $this->response = null;
    }

    return $response;
  }

  protected function sendStatus() {
    $status = null;
    if (is_numeric($this->status) && ((int) $this->status >= 100 && (int) $this->status <= 505)) {
      $status = (int) $this->status;
    } else {
      $status = 200;
    }

    http_response_code($status);
  }

  protected function sendHeaders() {
    if (is_array($this->headers) && count($this->headers) > 0) {
      foreach ($this->headers as $header) {
        if (is_string($header) && $header !== "") {
          header($header);
        }
      }
    }
  }

  protected function sendResponse() {
    if (is_null($this->response) === false) {
      echo $this->response;
    }
  }

  protected function resetHeaders() {
    $this->headers = array(
      "Access-Control-Allow-Origin: *",
      "Content-Type: application/json; charset=UTF-8",
      "Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE",
    );
  }

  protected function parseURI() {
    $this->uri = (int) trim(str_ireplace(array($this->endpoint, "/"), "", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
  }

  protected function parseMethod() {
    $this->method = strtolower($_SERVER['REQUEST_METHOD']);
  }

  protected function parseQuery() {
    if (is_array($_GET) && count($_GET) > 0) {
      $this->query = $_GET;
    } else {
      $this->query = array();
    }
  }

  protected function parseData() {
    switch (true) {
      case is_array($_POST) && count($_POST) > 0:
        $this->data = $_POST;
        break;
      case $this->method === "post":
      case $this->method === "put":
        $json = file_get_contents('php://input');
        $this->data = json_decode($json);
        break;
      default:
        $this->data = array();
        break;
    }
  }

  protected function all() {
    try {
      $result = $this->database->all();

      if (is_array($result)) {
        $response = $result;
      } else {
        $response = array();
      }

      $status = 200;
    } catch (Exception $err) {
      $status = 400;
      $response = array(
        "error" => true,
        "message" => "Unable to fetch tasks.",
      );
    }

    $this->setStatus($status);
    $this->setResponse($response);
  }

  protected function fetch() {
    try {
      $result = $this->database->fetch($this->uri);

      if (is_object($result) && get_class($result) === "\Tasker\Task") {
        $response = $result;
        $status = 200;
      } else {
        throw new Exception("Unable to locate task", 404);
      }
    } catch (Exception $err) {
      $status = $err->getCode();
      $response = array(
        "error" => true,
        "message" => $err->getMessage(),
      );
    }

    $this->setStatus($status);
    $this->setResponse($response);
  }

  protected function create() {
    try {
      if (is_object($this->data)) {
        $result = $this->database->create($this->data);

        if (is_object($result) && is_numeric($result->id)) {
          $response = $result;
          $status = 200;
        } else {
          throw new Exception("Unable to create task", 404);
        }
      } else {
        throw new Exception("Invalid task task", 400);
      }
    } catch (Exception $err) {
      $status = $err->getCode();
      $response = array(
        "error" => true,
        "message" => $err->getMessage(),
      );
    }

    $this->setStatus($status);
    $this->setResponse($response);
  }

  protected function update() {
    try {
      if (is_object($this->data)) {
        $result = $this->database->update($this->data);

        if (is_object($result) && is_numeric($result->id)) {
          $response = $result;
          $status = 200;
        } else {
          throw new Exception("Unable to update task", 404);
        }
      } else {
        throw new Exception("Invalid task task", 400);
      }
    } catch (Exception $err) {
      $status = $err->getCode();
      $response = array(
        "error" => true,
        "message" => $err->getMessage(),
      );
    }

    $this->setStatus($status);
    $this->setResponse($response);
  }

  protected function delete() {
    try {
      if (is_numeric($this->uri) && (int) $this->uri > 0) {
        $result = $this->database->delete((int) $this->uri);

        if (is_bool($result) && $result === true) {
          $response = $result;
          $status = 200;
        } else {
          throw new Exception("Unable to remove task", 400);
        }
      } else {
        throw new Exception("Invalid task task", 400);
      }
    } catch (Exception $err) {
      $status = $err->getCode();
      $response = array(
        "error" => true,
        "message" => $err->getMessage(),
      );
    }

    $this->setStatus($status);
    $this->setResponse($response);
  }

  public function __destruct() {

  }
}
?>
