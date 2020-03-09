<?php
namespace Tasker;

use Exception;
use JsonSerializable;

class Task implements JsonSerializable {
  private $_id = null;
  private $_title = null;
  private $_content = null;
  private $_completed = null;
  private $_due_on = null;
  private $_created = null;
  private $_modified = null;

  public function __construct($data = null) {
    if (is_array($data) || is_object($data)) {
      foreach ($data as $key => $value) {
        $this->$key = $value;
      }
    }
  }

  public function __set($name, $value) {
    if (method_exists($this, $name)) {
      $this->$name($value);
    }

    return $value;
  }

  public function __get($name) {
    if (method_exists($this, $name)) {
      $value = $this->$name();
    }

    return $value;
  }

  public function validate($mode = create) {
    switch ($mode) {
      case "update":
        if ($this->id() === 0) {
          throw new Exception("Invalid task id", 400);
        }
        if ($this->title() === "") {
          throw new Exception("Invalid task title", 400);
        }
        break;
      default:
        if ($this->title() === "") {
          throw new Exception("Invalid task title", 400);
        }
        break;
    }
  }

  protected function id($value = null) {
    switch (true) {
      case is_numeric($value) && (int) $value > 0:
        $this->_id = (int) $value;
        break;
      default:
        return $this->_id;
        break;
    }
  }

  protected function title($value = null) {
    switch (true) {
      case is_string($value) && $task === "":
        $this->_title = "A new task";
        break;
      case is_string($value) && strlen($value) <= 255:
        $this->_title = trim($value);
        break;
      case is_string($value) && strlen($value) > 255:
        $this->_title = trim(substr($value, 0, 252) . "...");
        break;
      default:
        return $this->_title;
        break;
    }
  }

  protected function content($value = null) {
    switch (true) {
      case is_string($value):
        $this->_content = $value;
        break;
      default:
        return $this->_content;
        break;
    }
  }

  protected function completed($value = null) {
    switch (true) {
      case is_numeric($value) && ((int) $value === 0 || (int) $value === 1):
        $this->_completed = (int) $value;
        break;
      case is_bool($value) && $value === true:
        $this->_completed = 1;
        break;
      case is_bool($value) && $value === false:
        $this->_completed = 0;
        break;
      default:
        return $this->_completed;
        break;
    }
  }

  protected function due_on($value = null) {
    switch (true) {
      case is_string($value) && $value !== "" && strtotime($value) !== false:
        $this->_due_on = date("Y-m-d", strtotime($value));
        break;
      default:
        return $this->_due_on;
        break;
    }
  }

  protected function created($value = null) {
    switch (true) {
      case is_string($value) && $value !== "" && strtotime($value) !== false:
        $this->_created = date("Y-m-d H:i:s", strtotime($value));
        break;
      case is_string($value) && $value === "":
      case is_string($value) && strtotime($value) === false:
        $this->_due_on = date("Y-m-d H:i:s", strtotime("now"));
        break;
      default:
        return $this->_created;
        break;
    }
  }

  protected function modified($value = null) {
    switch (true) {
      case is_string($value) && $value !== "" && strtotime($value) !== false:
        $this->_modified = date("Y-m-d H:i:s", strtotime($value));
        break;
      case is_string($value) && $value === "":
      case is_string($value) && strtotime($value) === false:
        $this->_due_on = date("Y-m-d H:i:s", strtotime("now"));
        break;
      default:
        return $this->_modified;
        break;
    }
  }

  public function __debugInfo() {
    return array(
      "id" => $this->id(),
      "title" => $this->title(),
      "content" => $this->content(),
      "completed" => $this->completed(),
      "due_on" => $this->due_on(),
      "created" => $this->created(),
      "modified" => $this->modified(),
    );
  }

  public function jsonSerialize() {
    return array(
      "id" => $this->id(),
      "title" => $this->title(),
      "content" => $this->content(),
      "completed" => $this->completed(),
      "due_on" => $this->due_on(),
      "created" => $this->created(),
      "modified" => $this->modified(),
    );
  }

  public function __destruct() {

  }
}
?>
