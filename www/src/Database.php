<?php
namespace Tasker;

use Tasker\Task;
use Exception;
use PDO;
use PDOException;

class Database {
  private $db = null;

  protected $dsn = null;
  protected $options = null;
  protected $credentials = null;

  public function __construct($data = null) {
    if (is_object($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key) && $key !== "db") {
          $this->$key = $value;
        }
      }
    }

    if (is_string($this->dsn) === false || (is_string($this->dsn) && $this->dsn === "")) {
      $host = getenv('DB_HOST');
      $port = getenv('DB_PORT');
      $charset = getenv('DB_CHARSET');
      $db = getenv('DB_DATABASE');

      $this->dsn = "mysql:host=$host;port=$port;charset=$charset;dbname=$db";
    }

    if (is_array($this->options) === false || (is_array($this->options) && count($this->options) === 0)) {
      $this->options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      );
    }

    switch (true) {
      case is_object($this->credentials) === false:
      case is_object($this->credentials) && property_exists($this->credentials, "username") === false:
      case is_object($this->credentials) && property_exists($this->credentials, "password") === false:
        $this->credentials = (object) array(
          "username" => getenv('DB_USERNAME'),
          "password" => getenv('DB_PASSWORD'),
        );
        break;
      default:

        break;
    }
  }

  public function connect() {
    if (is_resource($this->db) === false) {
      try {
        $this->db = new PDO($this->dsn, $this->credentials->username, $this->credentials->password, $this->options);
      } catch (Exception $err) {
        throw new PDOException($err->getMessage(), (int) $err->getCode());
      }
    }
  }

  protected function isTask($task) {
    $is = false;

    if (is_object($task) && get_class($task) === "Tasker\Task") {
      $is = true;
    }

    return $is;
  }

  public function all() {
    $tasks = null;

    try {
      $tasks = $this->db->query("SELECT * FROM tasks ORDER BY created asc")->fetchAll(PDO::FETCH_CLASS, "\Tasker\Task");
    } catch (Exception $err) {
      throw new PDOException($err->getMessage(), (int) $err->getCode());
    }

    return $tasks;
  }

  public function fetch($id) {
    $task = null;
    $call = null;
    $result = null;

    if (is_numeric((int) $id) && (int) $id > 0) {
      try {
        $call = $this->db->prepare("SELECT * FROM tasks WHERE `id` = :id");
        $call->bindValue(":id", $id, PDO::PARAM_INT);
        $call->execute();

        $result = $call->fetch(PDO::FETCH_OBJ);

        if (is_object($result)) {
          $task = new Task($result);
        }

        $call = null;
        $result = null;
      } catch (Exception $err) {
        throw new PDOException($err->getMessage(), (int) $err->getCode());
      }
    } else {
      throw new Exception("Invalid task id", 400);
    }

    return $task;
  }

  public function create($obj) {
    $id = null;
    $task = null;
    $call = null;
    $result = null;

    if (is_object($obj)) {
      $task = new Task($obj);
      $task->validate("create");

      try {
        $call = $this->db->prepare("INSERT INTO tasks (`title`, `content`, `completed`, `due_on`) VALUES (:title, :content, :completed, :due_on)");

        $call->bindValue(":title", $task->title, PDO::PARAM_STR);
        $call->bindValue(":content", $task->content, PDO::PARAM_STR);
        $call->bindValue(":completed", $task->completed, PDO::PARAM_BOOL);
        $call->bindValue(":due_on", $task->due_on, PDO::PARAM_STR);

        $result = $call->execute();

        if ($result === true) {
          $call = null;
          $result = null;

          $id = $this->db->lastInsertId();

          $task = $this->fetch($id);
        } else {
          throw new Exception("Unable to create task", 500);
        }
      } catch (Exception $err) {
        throw new PDOException($err->getMessage(), (int) $err->getCode());
      }
    } else {
      throw new Exception("Invalid Task", 500);
    }

    return $task;
  }

  public function update($obj) {
    $id = null;
    $call = null;
    $result = null;

    if (is_object($obj)) {
      try {
        $task = new Task($obj);
        $task->validate("update");
        $id = $task->id;

        $call = $this->db->prepare("UPDATE tasks set `title` = :title, `content` = :content, `completed` = :completed, `due_on` = :due_on WHERE id = :id");

        $call->bindValue(":id", $id, PDO::PARAM_INT);
        $call->bindValue(":title", $task->title, PDO::PARAM_STR);
        $call->bindValue(":content", $task->content, PDO::PARAM_STR);
        $call->bindValue(":completed", $task->completed, PDO::PARAM_BOOL);
        $call->bindValue(":due_on", $task->due_on, PDO::PARAM_STR);

        $result = $call->execute();

        if ($result === true) {
          $task = $this->fetch($id);

          $call = null;
          $result = null;
        } else {
          throw new Exception("Unable to update task", 500);
        }
      } catch (Exception $err) {
        throw new PDOException($err->getMessage(), (int) $err->getCode());
      }
    } else {
      throw new Exception("Invalid task", 400);
    }

    return $task;
  }

  public function delete($id) {
    $call = null;
    $count = null;
    $result = null;

    if (is_numeric((int) $id) && (int) $id > 0) {
      try {
        $call = $this->db->prepare("DELETE FROM tasks WHERE `id` = :id");
        $call->bindValue(":id", $id, PDO::PARAM_INT);

        $call->execute();

        $count = $call->rowCount();

        if ($count === 1) {
          $result = true;
        } else {
          $result = false;
        }

        $call = null;
        $count = null;
      } catch (Exception $err) {
        throw new PDOException($err->getMessage(), (int) $err->getCode());
      }
    } else {
      throw new Exception("Invalid task id", 400);
    }

    return $result;
  }

  public function __destruct() {
    $this->db = null;
  }
}
?>
