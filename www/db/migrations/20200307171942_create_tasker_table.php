<?php

use Phinx\Migration\AbstractMigration;

class CreateTaskerTable extends AbstractMigration {

  public function up() {
    $tasker = $this->table("tasks");

    $tasker->addColumn("title", "string", array("limit" => 255, "null" => false));
    $tasker->addColumn("content", "text");
    $tasker->addColumn("completed", "boolean", array("default" => 0, "null" => false));
    $tasker->addColumn("due_on", "date", array("null" => true));
    $tasker->addColumn("created", "timestamp", array("default" => "CURRENT_TIMESTAMP", "null" => false));
    $tasker->addColumn("modified", "timestamp", array("default" => "CURRENT_TIMESTAMP", "update" => "CURRENT_TIMESTAMP", "null" => false));

    $tasker->create();
  }

  public function down() {
    $this->table("tasks")->drop()->save();
  }
}
