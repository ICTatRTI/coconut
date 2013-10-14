// Generated by CoffeeScript 1.6.3
var Question, _ref,
  __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; },
  __hasProp = {}.hasOwnProperty,
  __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };

Question = (function(_super) {
  __extends(Question, _super);

  function Question() {
    this.summaryFieldNames = __bind(this.summaryFieldNames, this);
    this.resultSummaryFields = __bind(this.resultSummaryFields, this);
    _ref = Question.__super__.constructor.apply(this, arguments);
    return _ref;
  }

  Question.prototype.hint = function() {
    return this.safeGet('hint', '');
  };

  Question.prototype.type = function() {
    return this.safeGet("type", "text");
  };

  Question.prototype.label = function() {
    return this.safeGet("label", this.get('id'));
  };

  Question.prototype.safeLabel = function() {
    return this.label().replace(/[^a-zA-Z\u00E0-\u00FC0-9 -]/g, "").replace(/[ -]/g, "");
  };

  Question.prototype.repeatable = function() {
    return this.get("repeatable") === "true" || this.get("repeatable") === true;
  };

  Question.prototype.questions = function() {
    return this.safeGet("questions", []);
  };

  Question.prototype.skipLogic = function() {
    return this.safeGet("skip_logic", '');
  };

  Question.prototype.actionOnChange = function() {
    return this.safeGet("action_on_change", '');
  };

  Question.prototype.actionOnQuestionsLoaded = function() {
    return this.safeGet("action_on_questions_loaded", '');
  };

  Question.prototype.value = function() {
    return this.safeGet("value", "");
  };

  Question.prototype.required = function() {
    return this.safeGet("required", true);
  };

  Question.prototype.validation = function() {
    return this.safeGet("validation", null);
  };

  Question.prototype.warning = function() {
    return this.safeGet("warning", null);
  };

  Question.prototype.attributeSafeText = function() {
    var returnVal;
    returnVal = this.safeGet("label", this.get('id'));
    return returnVal.replace(/[^a-zA-Z\u00E0-\u00FC0-9]/g, "");
  };

  Question.prototype.url = "/question";

  Question.prototype.get = function(key) {
    if (key === "id") {
      return this.get("_id");
    }
    return Question.__super__.get.call(this, key);
  };

  Question.prototype.safeGet = function(attribute, defaultValue) {
    var value;
    value = this.get(attribute);
    if (value != null) {
      return value;
    }
    return defaultValue;
  };

  Question.prototype.set = function(attributes) {
    if (attributes.questions != null) {
      attributes.questions = _.map(attributes.questions, function(question) {
        return new Question(question);
      });
    }
    if (attributes.id != null) {
      attributes._id = attributes.id;
    }
    return Question.__super__.set.call(this, attributes);
  };

  Question.prototype.loadFromDesigner = function(domNode) {
    var attribute, property, result, _i, _len, _ref1;
    result = Question.fromDomNode(domNode);
    if (result.length === 1) {
      result = result[0];
      this.set({
        id: result.id
      });
      _ref1 = ["label", "type", "repeatable", "required", "validation"];
      for (_i = 0, _len = _ref1.length; _i < _len; _i++) {
        property = _ref1[_i];
        attribute = {};
        attribute[property] = result.get(property);
        this.set(attribute);
      }
      return this.set({
        questions: result.questions()
      });
    } else {
      throw "More than one root node";
    }
  };

  Question.prototype.resultSummaryFields = function() {
    var numberOfFields, resultSummaryFields, returnValue, _i, _results,
      _this = this;
    resultSummaryFields = this.get("resultSummaryFields");
    if (resultSummaryFields) {
      return resultSummaryFields;
    } else {
      numberOfFields = Math.min(2, this.questions().length - 1);
      returnValue = {};
      _.each((function() {
        _results = [];
        for (var _i = 0; 0 <= numberOfFields ? _i <= numberOfFields : _i >= numberOfFields; 0 <= numberOfFields ? _i++ : _i--){ _results.push(_i); }
        return _results;
      }).apply(this), function(index) {
        var _ref1;
        return returnValue[(_ref1 = _this.questions()[index]) != null ? _ref1.label() : void 0] = "on";
      });
      return returnValue;
    }
  };

  Question.prototype.summaryFieldNames = function() {
    return _.keys(this.resultSummaryFields());
  };

  Question.prototype.summaryFieldKeys = function() {
    return _.map(this.summaryFieldNames(), function(key) {
      return key.replace(/[^a-zA-Z0-9 -]/g, "").replace(/[ -]/g, "");
    });
  };

  return Question;

})(Backbone.Model);

Question.fromDomNode = function(domNode) {
  var _this = this;
  return _(domNode).chain().map(function(question) {
    var attribute, id, property, propertyValue, result, _i, _len, _ref1;
    question = $(question);
    id = question.attr("id");
    if (question.children("#rootQuestionName").length > 0) {
      id = question.children("#rootQuestionName").val();
    }
    if (!id) {
      return;
    }
    result = new Question;
    result.set({
      id: id
    });
    _ref1 = ["label", "type", "repeatable", "select-options", "radio-options", "autocomplete-options", "validation", "required", "action_on_questions_loaded", "skip_logic", "action_on_change", "image-path", "image-style"];
    for (_i = 0, _len = _ref1.length; _i < _len; _i++) {
      property = _ref1[_i];
      attribute = {};
      propertyValue = question.find("#" + property + "-" + id).val();
      if (property === "required") {
        propertyValue = String(question.find("#" + property + "-" + id).is(":checked"));
      }
      if (propertyValue != null) {
        attribute[property] = propertyValue;
        result.set(attribute);
      }
    }
    result.set({
      safeLabel: result.safeLabel()
    });
    if (question.find(".question-definition").length > 0) {
      result.set({
        questions: Question.fromDomNode(question.children(".question-definition"))
      });
    }
    return result;
  }).compact().value();
};
