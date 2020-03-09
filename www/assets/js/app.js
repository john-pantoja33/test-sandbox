;(function ($, window, document, undefined) {
  const endpoint = "tasks/";
  const selectors = {
    containers: {
      tasks: "#app-contents",
      deck: ".card-deck:first",
      form: "#form-contents",
    },
    templates: {
      task: "#template-task-card",
      form: "#template-task-form",
    },
    buttons: {
      add: ".add-task",
      edit: ".edit-task",
      remove: ".remove-task",
    },
    form: "#task-form",
    modal: "#form-modal",
  };

  const parseTask = (task) => {
    if (task instanceof Object) {
      switch (true) {
        case typeof task.completed === "number" && task.completed === 1:
        case typeof task.completed === "string" && task.completed === "Yes":
          task.completed = true;
          break;
        default:
          task.completed = false;
          break;
      }
    }

    return task;
  };

  const fetchTaskElements = (container) => {
    let task = null;

    if ($(container).length === 1) {
      task = {
        id: $(container).attr("task"),
        title: $(container).find(".task-title:first").text(),
        content: $(container).find(".task-content:first").text(),
        completed: $(container).find(".task-completed:first").text(),
        due_on: $(container).find(".task-due-on:first").text(),
        created: $(container).find(".task-created:first").text(),
        modified: $(container).find(".task-modified:first").text(),
      };
    }

    return task;
  };

  const fetchFormElements = (form) => {
    let task = null;

    if ($(form).length === 1) {
      task = {
        id: null,
        title: $(form).find('[name="title"]').val(),
        content: $(form).find('[name="content"]').val(),
        completed: Number.parseInt($(form).find('[name="completed"]').val(), 10),
        due_on: $(form).find('[name="due_on"]').val(),
        created: $(form).find('[name="created"]').val(),
        modified: $(form).find('[name="modified"]').val(),
      };

      if ($(form).find('[name="id"]').length === 1) {
        task.id = Number.parseInt($(form).find('[name="id"]').val(), 10);
      }
    }

    return task;
  };

  const Api = {
    all: () => {
      axios.get(endpoint).then((response) => {
        if (Array.isArray(response.data)) {
          Render.tasks(response.data);
        } else {
          console.log(response.data);
        }
      }).catch((err) => {
        console.log(err);
      });
    },
    create: (task) => {
      if (task instanceof Object) {
        axios.post(endpoint, task).then((response) => {
          if (response.data instanceof Object) {
            Render.task(response.data);
          } else {
            console.log(response.data);
          }
        }).catch((err) => {
          console.log(err);
        });
      }
    },
    update: (task) => {
      if (task instanceof Object) {
        axios.put(`${endpoint}${task.id}`, task).then((response) => {
          if (response.data instanceof Object) {
            Render.update(response.data);
          } else {
            console.log(response.data);
          }
        }).catch((err) => {
          console.log(err);
        });
      }
    },
    remove: (task) => {
      if (task instanceof Object) {
        axios.delete(`${endpoint}${task.id}`).then((response) => {
          if (typeof response.data === "boolean" && response.data === true) {
            Render.remove(task);
          } else {
            console.log(response.data);
          }
        }).catch((err) => {
          console.log(err);
        });
      }
    },
  };

  const Render = {
    pre: (container) => {
      $(container).empty();
    },
    tasks: (tasks) => {
      let card = null;
      let deck = null;
      let contents = null;

      Render.pre(selectors.containers.deck);

      if (Array.isArray(tasks) && tasks.length > 0) {
        card = Handlebars.compile($(selectors.templates.task).html(), { preventIndent: true });
        contents = [];

        $.each(tasks, (index, task) => {
          contents.push(card(parseTask(task)));
        });

        $(selectors.containers.deck).append(contents);
      }
    },
    task: (task) => {
      let template = null;
      let contents = null;

      if (task instanceof Object) {
        template = Handlebars.compile($(selectors.templates.task).html(), { preventIndent: true });
        contents = template(parseTask(task));
        $(selectors.containers.deck).append(contents);

      }

      $(selectors.modal).modal("hide");
    },
    form: (task, mode) => {
      let template = null;
      let contents = null;
      let data = null;

      Render.pre(selectors.containers.form);

      template = Handlebars.compile($(selectors.templates.form).html());

      data = {
        header: "Creating Task",
        remove: false,
        method: "post",
        buttons: {
          submit: "Create",
          cancel: "Cancel",
        },
        task: task,
      };

      if (typeof mode === "string" && mode !== "") {
        switch (mode) {
          case "edit":
            data.header = "Updating Task";
            data.buttons.submit = "Update";
            data.method = "put";
            break;
          case "remove":
            data.header = "Removing Task";
            data.buttons.submit = "Remove";
            data.method = "delete";
            data.remove = true;
            break;
          default:
            data.header = "Creating Task";
            data.buttons.submit = "Create";
            data.method = "post";
            break;
        }
      }

      contents = template(data);

      $(selectors.containers.form).append(contents);
    },
    update: (task) => {
      let container = null;
      let template = null;
      let contents = null;

      if (task instanceof Object) {
        if (typeof task.id === "number") {
          container = $(`#task-${task.id}`);

          if ($(container).length === 1) {
            template = Handlebars.compile($(selectors.templates.task).html(), { preventIndent: true });
            contents = template(parseTask(task));
            $(container).parent().replaceWith(contents);
          }
        }

        $(selectors.modal).modal("hide");
      }
    },
    remove: (task) => {
      let container = null;

      if (task instanceof Object) {
        if (typeof task.id === "number") {
          container = $(`#task-${task.id}`);

          if ($(container).length === 1) {
            Api.all();
          }
        }

        $(selectors.modal).modal("hide");
      }
    },
  };

  $(document).on("click", '[name="completed"]', (e) => {
    if ($(e.target).is(":checked")) {
      $(e.target).val("1");
    } else {
      $(e.target).val("0");
    }
  });

  $(document).on("reset", selectors.form, (e) => {
    $(selectors.modal).modal("hide");
  })

  $(document).on("submit", selectors.form, (e) => {
    const form = $(e.target);
    const method = $(form).attr("method");

    let task = {
      id: null,
      title: null,
      content: null,
      completed: null,
      due_on: null,
      created: null,
      modified: null,
    };

    e.preventDefault();

    task = fetchFormElements(form);

    switch (method) {
      case "put":
        Api.update(task);
        break;
      case "delete":
        Api.remove(task);
        break;
      default:
        Api.create(task);
        break;
    }
  });

  $(document).on("click", selectors.buttons.add, (e) => {
    Render.form();
  });

  $(document).on("click", selectors.buttons.edit, (e) => {
    const container = $(e.target).parents(".card:first");
    let task = null;

    if ($(container).length === 1) {
      task = fetchTaskElements(container);

      Render.form(parseTask(task), "edit");

      $(selectors.modal).modal("show");
    }
  });

  $(document).on("click", selectors.buttons.remove, (e) => {
    const container = $(e.target).parents(".card:first");
    let task = null;

    if ($(container).length === 1) {
      task = {
        id: $(container).attr("task"),
        id: $(container).attr("task"),
        title: $(container).find(".task-title:first").text(),
        content: $(container).find(".task-content:first").text(),
        completed: $(container).find(".task-completed:first").text(),
        due_on: $(container).find(".task-due-on:first").text(),
        created: $(container).find(".task-created:first").text(),
        modified: $(container).find(".task-modified:first").text(),
      };

      Render.form(parseTask(task), "remove");

      $(selectors.modal).modal("show");
    }
  });

  $(document).on("hide.bs.modal", selectors.modal, (e) => {
    $(selectors.containers.form).empty();
  });

  $(document).ready(() => {
    Api.all();
  });

})(jQuery, window, document);
