// Generated by CoffeeScript 1.3.1
var QuestionView,
  __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; },
  __hasProp = {}.hasOwnProperty,
  __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor; child.__super__ = parent.prototype; return child; };

QuestionView = (function(_super) {

  __extends(QuestionView, _super);

  QuestionView.name = 'QuestionView';

  function QuestionView() {
    this.render = __bind(this.render, this);
    return QuestionView.__super__.constructor.apply(this, arguments);
  }

  QuestionView.prototype.initialize = function() {
    var _ref;
    return (_ref = Coconut.resultCollection) != null ? _ref : Coconut.resultCollection = new ResultCollection();
  };

  QuestionView.prototype.el = '#content';

  QuestionView.prototype.render = function() {
    this.$el.html("      <div style='position:fixed; right:5px; color:white; background-color: #333; padding:20px; display:none; z-index:10' id='messageText'>        Saving...      </div>      <div id='question-view'>        <form>          " + (this.toHTMLForm(this.model)) + "        </form>      </div>    ");
    js2form($('form').get(0), this.result.toJSON());
    this.$el.find("input[type=text],input[type=number],input[type='autocomplete from previous entries']").textinput();
    this.$el.find('input[type=radio],input[type=checkbox]').checkboxradio();
    this.$el.find('ul').listview();
    this.$el.find('a').button();
    this.$el.find('input[type=date]').datebox({
      mode: "calbox"
    });
    _.each($("input[type='autocomplete from list'],input[type='autocomplete from previous entries']"), function(element) {
      var source;
      element = $(element);
      if (element.attr("type") === 'autocomplete from list') {
        source = element.attr("data-autocomplete-options").split(/, */);
      } else {
        source = document.location.pathname.substring(0, document.location.pathname.indexOf("index.html")) + ("_list/values/byValue?key=\"" + (element.attr("name")) + "\"");
      }
      return element.autocomplete({
        source: source,
        target: "#" + (element.attr("id")) + "-suggestions",
        callback: function(event) {
          element.val($(event.currentTarget).text());
          return element.autocomplete('clear');
        }
      });
    });
    $("input[name=complete]").closest("div.question").prepend("        <div style='background-color:yellow' id='validationMessage'></div>      ");
    if (this.readonly) {
      return $('input,textarea').attr("readonly", "true");
    }
  };

  QuestionView.prototype.events = {
    "change #question-view input": "save",
    "change #question-view select": "save",
    "click #question-view button:contains(+)": "repeat",
    "click #question-view a:contains(Get current location)": "getLocation"
  };

  QuestionView.prototype.getLocation = function(event) {
    var question_id,
      _this = this;
    question_id = $(event.target).closest("[data-question-id]").attr("data-question-id");
    $("#" + question_id + "-description").val("Retrieving position, please wait.");
    return navigator.geolocation.getCurrentPosition(function(geoposition) {
      _.each(geoposition.coords, function(value, key) {
        return $("#" + question_id + "-" + key).val(value);
      });
      $("#" + question_id + "-timestamp").val(moment(geoposition.timestamp).format(Coconut.config.get("date_format")));
      $("#" + question_id + "-description").val("Success");
      _this.save();
      return $.getJSON("http://api.geonames.org/findNearbyPlaceNameJSON?lat=" + geoposition.coords.latitude + "&lng=" + geoposition.coords.longitude + "&username=mikeymckay&callback=?", null, function(result) {
        $("#" + question_id + "-description").val(parseFloat(result.geonames[0].distance).toFixed(1) + " km from center of " + result.geonames[0].name);
        return _this.save();
      });
    }, function(error) {
      return $("#" + question_id + "-description").val("Error: " + error);
    }, {
      frequency: 1000,
      enableHighAccuracy: true,
      timeout: 30000,
      maximumAge: 0
    });
  };

  QuestionView.prototype.validate = function(result) {
    var _ref,
      _this = this;
    $("#validationMessage").html("");
    _.each(result, function(value, key) {
      return $("#validationMessage").append(_this.validateItem(value, key));
    });
    _.chain($("input[type=radio]")).map(function(element) {
      return $(element).attr("name");
    }).uniq().map(function(radioName) {
      var labelID, labelText, question, required, _ref;
      console.log(radioName);
      question = $("input[name=" + radioName + "]").closest("div.question");
      required = question.attr("data-required") === "true";
      if (required && !$("input[name=" + radioName + "]").is(":checked")) {
        labelID = question.attr("data-question-id");
        labelText = (_ref = $("label[for=" + labelID + "]")) != null ? _ref.text() : void 0;
        return $("#validationMessage").append("'" + labelText + "' is required<br/>");
      }
    });
    if ($("#validationMessage").html() !== "") {
      if ((_ref = $("input[name=complete]")) != null) {
        _ref.prop("checked", false);
      }
      return false;
    } else {
      return true;
    }
  };

  QuestionView.prototype.validateItem = function(value, question_id) {
    var labelText, question, required, result, validation, validationFunction, _ref;
    result = [];
    question = $("[name=" + question_id + "]");
    labelText = (_ref = $("label[for=" + (question.attr("id")) + "]")) != null ? _ref.text() : void 0;
    required = question.closest("div.question").attr("data-required") === "true";
    validation = unescape(question.closest("div.question").attr("data-validation"));
    if (required && !(value != null)) {
      result.push("'" + labelText + "' is required (NA or 9999 may be used if information not available)");
    }
    if (validation !== "undefined" && validation !== null) {
      validationFunction = eval("(function(value){" + validation + "})");
      result.push(validationFunction(value));
    }
    result = _.compact(result);
    if (result.length > 0) {
      return result.join("<br/>") + "<br/>";
    } else {
      return "";
    }
  };

  QuestionView.prototype.save = function() {
    var currentData,
      _this = this;
    currentData = $('form').toObject({
      skipEmpty: false
    });
    if (currentData.complete && !this.validate(currentData)) {
      return;
    }
    this.result.save(_.extend(currentData, {
      lastModifiedAt: moment(new Date()).format(Coconut.config.get("date_format")),
      savedBy: $.cookie('current_user')
    }), {
      success: function() {
        return $("#messageText").slideDown().fadeOut();
      }
    });
    this.key = "MalariaCaseID";
    Coconut.menuView.update();
    if (this.result.complete()) {
      if (this.result.nextLevelCreated !== true) {
        this.result.nextLevelCreated = true;
        return Coconut.resultCollection.fetch({
          success: function() {
            var result;
            switch (_this.result.get('question')) {
              case "Case Notification":
                if (!_this.currentKeyExistsInResultsFor('Facility')) {
                  result = new Result({
                    question: "Facility",
                    MalariaCaseID: _this.result.get("MalariaCaseID"),
                    FacilityName: _this.result.get("FacilityName")
                  });
                  return result.save(null, {
                    success: function() {
                      return Coconut.menuView.update();
                    }
                  });
                }
                break;
              case "Facility":
                if (!_this.currentKeyExistsInResultsFor('Household')) {
                  result = new Result({
                    question: "Household",
                    MalariaCaseID: _this.result.get("MalariaCaseID"),
                    HeadofHouseholdName: _this.result.get("HeadofHouseholdName")
                  });
                  return result.save(null, {
                    success: function() {
                      return Coconut.menuView.update();
                    }
                  });
                }
                break;
              case "Household":
                if (!_this.currentKeyExistsInResultsFor('Household Members')) {
                  return _(_this.result.get("TotalNumberofResidentsintheHousehold")).times(function() {
                    result = new Result({
                      question: "Household Members",
                      MalariaCaseID: _this.result.get("MalariaCaseID"),
                      HeadofHouseholdName: _this.result.get("HeadofHouseholdName")
                    });
                    return result.save(null, {
                      success: function() {
                        return Coconut.menuView.update();
                      }
                    });
                  });
                }
            }
          }
        });
      }
    }
  };

  QuestionView.prototype.currentKeyExistsInResultsFor = function(question) {
    var _this = this;
    return Coconut.resultCollection.any(function(result) {
      return _this.result.get(_this.key) === result.get(_this.key) && result.get('question') === question;
    });
  };

  QuestionView.prototype.repeat = function(event) {
    var button, inputElement, name, newIndex, newQuestion, questionID, re, _i, _len, _ref;
    button = $(event.target);
    newQuestion = button.prev(".question").clone();
    questionID = newQuestion.attr("data-group-id");
    if (questionID == null) {
      questionID = "";
    }
    _ref = newQuestion.find("input");
    for (_i = 0, _len = _ref.length; _i < _len; _i++) {
      inputElement = _ref[_i];
      inputElement = $(inputElement);
      name = inputElement.attr("name");
      re = new RegExp("" + questionID + "\\[(\\d)\\]");
      newIndex = parseInt(_.last(name.match(re))) + 1;
      inputElement.attr("name", name.replace(re, "" + questionID + "[" + newIndex + "]"));
    }
    button.after(newQuestion.add(button.clone()));
    return button.remove();
  };

  QuestionView.prototype.toHTMLForm = function(questions, groupId) {
    var _this = this;
    if (questions == null) {
      questions = this.model;
    }
    if (questions.length == null) {
      questions = [questions];
    }
    return _.map(questions, function(question) {
      var name, newGroupId, options, question_id, repeatable;
      if (question.onChange) {
        console.log(question.onChange);
      }
      if (question.repeatable() === "true") {
        repeatable = "<button>+</button>";
      } else {
        repeatable = "";
      }
      if ((question.type() != null) && (question.label() != null) && question.label() !== "") {
        name = question.label().replace(/[^a-zA-Z0-9 -]/g, "").replace(/[ -]/g, "");
        question_id = question.get("id");
        if (question.repeatable() === "true") {
          name = name + "[0]";
          question_id = question.get("id") + "-0";
        }
        if (groupId != null) {
          name = "group." + groupId + "." + name;
        }
        return "          <div             " + (question.validation() ? question.validation() ? "data-validation = '" + (escape(question.validation())) + "'" : void 0 : "") + "             data-required='" + (question.required()) + "'             class='question'            data-question-id='" + question_id + "'          >" + (!question.type().match(/hidden/) ? "<label type='" + (question.type()) + "' for='" + question_id + "'>" + (question.label()) + " <span></span></label>" : void 0) + "          " + ((function() {
          switch (question.type()) {
            case "textarea":
              return "<input name='" + name + "' type='text' id='" + question_id + "' value='" + (question.value()) + "'></input>";
            case "radio":
            case "select":
              if (this.readonly) {
                return "<input name='" + name + "' type='text' id='" + question_id + "' value='" + (question.value()) + "'></input>";
              } else {
                options = question.get("radio-options") || question.get("select-options");
                return _.map(options.split(/, */), function(option, index) {
                  return "                      <label for='" + question_id + "-" + index + "'>" + option + "</label>                      <input type='radio' name='" + name + "' id='" + question_id + "-" + index + "' value='" + option + "'/>                    ";
                }).join("");
              }
              break;
            case "checkbox":
              if (this.readonly) {
                return "<input name='" + name + "' type='text' id='" + question_id + "' value='" + (question.value()) + "'></input>";
              } else {
                return "<input style='display:none' name='" + name + "' id='" + question_id + "' type='checkbox' value='true'></input>";
              }
              break;
            case "autocomplete from list":
              return "                  <!-- autocomplete='off' disables browser completion -->                  <input autocomplete='off' name='" + name + "' id='" + question_id + "' type='" + (question.type()) + "' value='" + (question.value()) + "' data-autocomplete-options='" + (question.get("autocomplete-options")) + "'></input>                  <ul id='" + question_id + "-suggestions' data-role='listview' data-inset='true'/>                ";
            case "autocomplete from previous entries":
              return "                  <!-- autocomplete='off' disables browser completion -->                  <input autocomplete='off' name='" + name + "' id='" + question_id + "' type='" + (question.type()) + "' value='" + (question.value()) + "'></input>                  <ul id='" + question_id + "-suggestions' data-role='listview' data-inset='true'/>                ";
            case "location":
              return "                  <a data-question-id='" + question_id + "'>Get current location</a>                  <label for='" + question_id + "-description'>Location Description</label>                  <input type='text' name='" + name + "-description' id='" + question_id + "-description'></input>                  " + (_.map(["latitude", "longitude"], function(field) {
                return "<label for='" + question_id + "-" + field + "'>" + field + "</label><input readonly='readonly' type='number' name='" + name + "-" + field + "' id='" + question_id + "-" + field + "'></input>";
              }).join("")) + "                  " + (_.map(["altitude", "accuracy", "altitudeAccuracy", "heading", "timestamp"], function(field) {
                return "<input type='hidden' name='" + name + "-" + field + "' id='" + question_id + "-" + field + "'></input>";
              }).join("")) + "                ";
            case "image":
              return "<a>Get image</a>";
            default:
              return "<input name='" + name + "' id='" + question_id + "' type='" + (question.type()) + "' value='" + (question.value()) + "'></input>";
          }
        }).call(_this)) + "          </div>          " + repeatable + "        ";
      } else {
        newGroupId = question_id;
        if (question.repeatable()) {
          newGroupId = newGroupId + "[0]";
        }
        return ("<div data-group-id='" + question_id + "' class='question group'>") + _this.toHTMLForm(question.questions(), newGroupId) + "</div>" + repeatable;
      }
    }).join("");
  };

  return QuestionView;

})(Backbone.View);
