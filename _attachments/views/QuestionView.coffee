window.SkipTheseWhen = ( argQuestions, result ) ->
  questions = []
  argQuestions = argQuestions.split(/\s*,\s*/)
  for question in argQuestions
    questions.push $(".question[data-question-name=#{question}]")
  disabledClass = "disabled_skipped"

  for question in questions
    if result
      question.addClass disabledClass
    else
      question.removeClass disabledClass


window.ResultOfQuestion = ( name ) ->
  
  result = {}

  safeVal = ($result) -> 
    if $result.is(":visible")
      return ( $result.val() || '' ).trim()
    else
      throw "invisible reference"

  return safeVal(result) if (result = $(".question select[name=#{name}]")).length isnt 0
  if (result = $(".question input[name=#{name}]")).length isnt 0
    if result.attr("type") is "radio" or result.attr("type") is "checkbox"
      result = $(".question input[name=#{name}]:checked")
    return safeVal(result)
  return safeVal(result) if (result = $(".question textarea[name=#{name}]")).length != 0

class QuestionView extends Backbone.View

  initialize: ->
    Coconut.resultCollection ?= new ResultCollection()
    @autoscrollTimer = 0

  el: '#content'

  triggerChangeIn: (names) ->
    for name in names
      $(".question[data-question-name=#{name}] input, .question[data-question-name=#{name}] select, .question[data-question-name=#{name}] textarea").each (index, element) =>
        event = target : element
        @actionOnChange event

  render: =>
    @$el.html "
    <style>
      .message
      {
        color: grey;
        font-weight: bold;
        padding: 10px;
        border: 1px yellow dotted;
        background: yellow;
        display: none;
      }
    </style>
      <div style='position:fixed; right:5px; color:white; background-color: #333; padding:20px; display:none; z-index:10' id='messageText'>
        Saving...
      </div>
      <h1>#{@model.id}</h1>
      <div id='question-view'>
        <form>
          #{@toHTMLForm(@model)}
        </form>
      </div>
    "

    # for first run
    @updateSkipLogic()
    
    # skipperList is a list of questions that use skip logic in their action on change events
    skipperList = []

    _.each @model.get("questions"), (question) =>

      # remember which questions have skip logic in their actionOnChange code 
      skipperList.push(question.safeLabel()) if question.actionOnChange().match(/skip/i)
      
      if question.get("action_on_questions_loaded") isnt ""
        CoffeeScript.eval question.get "action_on_questions_loaded"

    js2form($('form').get(0), @result.toJSON())

    # Trigger a change event for each of the questions that contain skip logic in their actionOnChange code
    @triggerChangeIn skipperList

    @$el.find("input[type=text],input[type=number],input[type='autocomplete from previous entries'],input[type='autocomplete from list']").textinput()
    @$el.find('input[type=radio],input[type=checkbox]').checkboxradio()
    @$el.find('ul').listview()
    @$el.find('select').selectmenu()
    @$el.find('a').button()
    @$el.find('input[type=date]').datebox
      mode: "calbox"
      dateFormat: "%d-%m-%Y"

#    tagSelector = "input[name=Tags],input[name=tags]"
#    $(tagSelector).tagit
#      availableTags: [
#        "complete"
#      ]
#      onTagChanged: ->
#        $(tagSelector).trigger('change')

    _.each $("input[type='autocomplete from list'],input[type='autocomplete from previous entries']"), (element) ->
      element = $(element)
      if element.attr("type") is 'autocomplete from list'
        source = element.attr("data-autocomplete-options").replace(/\n|\t/,"").split(/, */)
        minLength = 0
      else
        source = document.location.pathname.substring(0,document.location.pathname.indexOf("index.html")) + "_list/values/byValue?key=\"#{element.attr("name")}\""
        minLength = 1

      element.autocomplete
        source: source
        minLength: minLength
        target: "##{element.attr("id")}-suggestions"
        callback: (event) ->
          element.val($(event.currentTarget).text())
          element.autocomplete('clear')

    $('input,textarea').attr("readonly", "true") if @readonly

  events:
    "blur #question-view input"      : "onChange"
    "change #question-view input"    : "onChange"
    "change #question-view select"   : "onChange"
    "change #question-view textarea" : "onChange"
    "click #question-view button:contains(+)" : "repeat"
    "click #question-view a:contains(Get current location)" : "getLocation"
    "click .next_error" : "runValidate"

  runValidate: -> @validate($('form').toObject(skipEmpty: false))

  onChange: (event) ->

    $target = $(event.target)

    #
    # Don't duplicate events unless 1 second later
    #
    eventStamp = $target.attr("id") + "-" + event.type + "/"

    return if eventStamp == @oldStamp and (new Date()).getTime() < @throttleTime + 1000

    @throttleTime = (new Date()).getTime()
    @oldStamp     = eventStamp

    if $target.attr("name") == "complete"
      @validate($('form').toObject(skipEmpty: false))

    @save()
    @updateSkipLogic()
    @actionOnChange(event)

    @autoscroll(event)


  autoscroll: (event) ->

    clearTimeout @autoscrollTimer

    $target = $(event.target)

    if $target.attr("type") == "radio"

      $div = $target.closest(".question")

      @$next = $div.next()

      @$next = $(@$next).next() while @$next.length != 0 && @$next.hasClass("disabled_skipped")

      if @$next.length != 0
        $(window).on( "scroll", => $(window).off("scroll"); clearTimeout @autoscrollTimer; )
        @autoscrollTimer = setTimeout(
          => 
            $(window).off( "scroll" )
            @$next.scrollTo()
          1000
        )

  # takes an event as an argument, and looks for an input, select or textarea inside the target of that event.
  # Runs the change code associated with that question.
  actionOnChange: ->

    nodeName = $(event.target).get(0).nodeName
    $target = 
      if nodeName is "INPUT" or nodeName is "SELECT" or nodeName is "TEXTAREA"
        $(event.target)
      else
        $(event.target).parent().parent().parent().find("input,textarea,select")

    name = $target.attr("name")
    $divQuestion = $(".question [data-question-name=#{name}]")
    code = $divQuestion.attr("data-action_on_change")
    try 
      value = ResultOfQuestion(name)
    catch error
      return if error == "invisible reference"

    return if code == "" or not code?
    code = "(value) -> #{code}"
    try
      newFunction = CoffeeScript.eval.apply(@, [code])
      newFunction(value)
    catch error
      name = ((/function (.{1,})\(/).exec(error.constructor.toString())[1])
      message = error.message
      alert "Action on change error in question #{$divQuestion.attr('data-question-id') || $divQuestion.attr("id")}\n\n#{name}\n\n#{message}"


  updateSkipLogic: ->
    _($(".question")).each (question) ->

      question = $(question)

      skipLogicCode = question.attr("data-skip_logic")

      return if skipLogicCode is "" or not skipLogicCode?

      try
        result = CoffeeScript.eval.apply(@, [skipLogicCode])
      catch error
        if error == "invisible reference"
          result = true
        else
          name = ((/function (.{1,})\(/).exec(error.constructor.toString())[1])
          message = error.message
          alert "Skip logic error in question #{question.attr('data-question-id')}\n\n#{name}\n\n#{message}"

      if result
        question.addClass "disabled_skipped"
      else
        question.removeClass "disabled_skipped"




  getLocation: (event) ->
    question_id = $(event.target).closest("[data-question-id]").attr("data-question-id")
    $("##{question_id}-description").val "Retrieving position, please wait."
    navigator.geolocation.getCurrentPosition(
      (geoposition) =>
        _.each geoposition.coords, (value,key) ->
          $("##{question_id}-#{key}").val(value)
        $("##{question_id}-timestamp").val(moment(geoposition.timestamp).format(Coconut.config.get "date_format"))
        $("##{question_id}-description").val "Success"
        @save()
        $.getJSON "http://api.geonames.org/findNearbyPlaceNameJSON?lat=#{geoposition.coords.latitude}&lng=#{geoposition.coords.longitude}&username=mikeymckay&callback=?", null, (result) =>
          $("##{question_id}-description").val parseFloat(result.geonames[0].distance).toFixed(1) + " km from center of " + result.geonames[0].name
          @save()
      (error) ->
        $("##{question_id}-description").val "Error: #{error}"
      {
        frequency: 1000
        enableHighAccuracy: true
        timeout: 30000
        maximumAge: 0
      }
    )

  validate: (result) ->

    first = true
    isValid = true # optimistic error checking

    nextButton = "<button type='button' class='next_error'>Next Error</button>"

    _.chain($("input[type=radio]"))
    .map (element) ->
      $(element).attr("name")
    .uniq()
    .map (radioName) ->
      result[radioName] = $("input[name=#{radioName}]:checked").val() || ""
      
    # Make the results object match the order of the questions on the screen

    questions  = $(".question")
    newResult = {}

    for question in questions
      $question = $(question)
      name = $(question).attr("data-question-name") 
      newResult[name] = result[name]


    _.each( newResult, 
      ( value, key ) =>
        $message = ( $question = $(".question[data-question-name=#{key}]") ).find(".message")
        $message.hide()
        try
          message = @validateItem(value, key)
        catch e
          alert "Validate item error in #{key}\n#{e}"
          message = ""
        
        return if message is ""
        $message.show()
        $message.html "
          #{message}
          #{nextButton}
        "
        if first && $question.length != 0
          $question.scrollTo()
          first = false
          isValid = false
    )

    @completeButton isValid

    $("[name=complete]").scrollTo() if isValid

    return isValid


    ###
    question = $("input[name=#{radioName}]").closest("div.question")
    required = question.attr("data-required") is "true"
    if required and not $("input[name=#{radioName}]").is(":checked") and not question.hasClass("disabled_skipped")
      labelID = question.attr("data-question-id")
      labelText = $("label[for=#{labelID}]")?.text()
      $("#validationMessage").append "'#{labelText}' is required<br/>"
    ###
    ###
    unless $("#validationMessage").html() is ""
      $("input[name=complete]")?.prop("checked", false)
      return false
    else
      return true
    ###
    
  validateItem: ( value = "", question_id ) ->
    return unless question_id
    result = []

    question        = $("[name=#{question_id}]")
    questionWrapper = $(".question[data-question-name=#{question_id}]")
    return "" if questionWrapper.hasClass("label")
    type            = $(questionWrapper.find("input").get(0)).attr("type")
    labelText       = 
      if type is "radio"
        $("label[for=#{question.attr("id").split("-")[0]}]").text() || ""
      else
        $("label[for=#{question.attr("id")}]")?.text()
    required        = questionWrapper.attr("data-required") is "true"
    validation      = unescape(questionWrapper.attr("data-validation"))
    validation      = null if validation is "undefined"

    #
    # Exit early conditions
    #

    # don't evaluate anything that's been skipped. Skipped = valid
    return "" if questionWrapper.hasClass("disabled_skipped")
    
    # "" = true
    return "" if question.find("input").length != 0 and (type == "checkbox" or type == "radio")

    if required && value is ""
      result.push "'#{labelText}' is required."

    if validation? && validation isnt ""

      

      try
        validationFunctionResult = (CoffeeScript.eval("(value) -> #{validation}", {bare:true}))(value)
        result.push validationFunctionResult if validationFunctionResult?
      catch error
        alert "Validation error for #{question_id} with value #{value}: #{error}"

    if result.length isnt 0
      return result.join("<br/>") + "<br/>"

    return ""

  # We throttle to limit how fast save can be repeatedly called
  save: _.throttle( ->

      currentData = $('form').toObject(skipEmpty: false)
      @result.save _.extend(
        # Make sure lastModifiedAt is always updated on save
        currentData
        {
          lastModifiedAt: moment(new Date())
            .format(Coconut.config.get "date_format")
          savedBy: $.cookie('current_user')
        }
      ),
        success: (model) ->
          $("#messageText").slideDown().fadeOut()

      # Update the menu
      Coconut.menuView.update()
    , 1000)

  completeButton: ( value ) ->
    if $('[name=complete]').prop("checked") isnt value
      $('[name=complete]').click()


  currentKeyExistsInResultsFor: (question) ->
    Coconut.resultCollection.any (result) =>
      @result.get(@key) == result.get(@key) and result.get('question') == question

  repeat: (event) ->
    button = $(event.target)
    newQuestion = button.prev(".question").clone()
    questionID = newQuestion.attr("data-group-id")
    questionID = "" unless questionID?

    # Fix the indexes
    for inputElement in newQuestion.find("input")
      inputElement = $(inputElement)
      name = inputElement.attr("name")
      re = new RegExp("#{questionID}\\[(\\d)\\]")
      newIndex = parseInt(_.last(name.match(re))) + 1
      inputElement.attr("name", name.replace(re,"#{questionID}[#{newIndex}]"))

    button.after(newQuestion.add(button.clone()))
    button.remove()

  toHTMLForm: (questions = @model, groupId) ->
    # Need this because we have recursion later
    questions = [questions] unless questions.length?
    _.map(questions, (question) =>

      if question.repeatable() == "true" then repeatable = "<button>+</button>" else repeatable = ""
      if question.type()? and question.label()? and question.label() != ""
        name = question.safeLabel()
        question_id = question.get("id")
        if question.repeatable() == "true"
          name = name + "[0]"
          question_id = question.get("id") + "-0"
        if groupId?
          name = "group.#{groupId}.#{name}"
        return "
          <div 
            #{
            if question.validation()
              "data-validation = '#{escape(question.validation())}'" if question.validation() 
            else
              ""
            } 
            data-required='#{question.required()}'
            class='question #{question.type?() or ''}'
            data-question-name='#{name}'
            data-question-id='#{question_id}'
            data-skip_logic='#{_.escape(question.skipLogic())}'
            data-action_on_change='#{_.escape(question.actionOnChange())}'

          >
          <div class='message'></div>
          #{
            "<label type='#{question.type()}' for='#{question_id}'>#{question.label()} <span></span></label>" unless question.type().match(/hidden/)
          }
          #{
            switch question.type()
              when "textarea"
                "<input name='#{name}' type='text' id='#{question_id}' value='#{_.escape(question.value())}'></input>"
# Selects look lame - use radio buttons instead or autocomplete if long list
#              when "select"
#                "
#                  <select name='#{name}'>#{
#                    _.map(question.get("select-options").split(/, */), (option) ->
#                      "<option>#{option}</option>"
#                    ).join("")
#                  }
#                  </select>
#                "
              when "select"
                if @readonly
                  question.value()
                else

                  html = "<select>"
                  for option, index in question.get("select-options").split(/, */)
                    html += "<option name='#{name}' id='#{question_id}-#{index}' value='#{option}'>#{option}</option>"
                  html += "</select>"
              when "radio"
                if @readonly
                  "<input name='#{name}' type='text' id='#{question_id}' value='#{question.value()}'></input>"
                else
                  options = question.get("radio-options")
                  _.map(options.split(/, */), (option,index) ->
                    "
                      <label for='#{question_id}-#{index}'>#{option}</label>
                      <input type='radio' name='#{name}' id='#{question_id}-#{index}' value='#{_.escape(option)}'/>
                    "
                  ).join("")
              when "checkbox"
                if @readonly
                  "<input name='#{name}' type='text' id='#{question_id}' value='#{_.escape(question.value())}'></input>"
                else
                  "<input style='display:none' name='#{name}' id='#{question_id}' type='checkbox' value='true'></input>"
              when "autocomplete from list", "autocomplete from previous entries"
                "
                  <!-- autocomplete='off' disables browser completion -->
                  <input autocomplete='off' name='#{name}' id='#{question_id}' type='#{question.type()}' value='#{question.value()}' data-autocomplete-options='#{question.get("autocomplete-options")}'></input>
                  <ul id='#{question_id}-suggestions' data-role='listview' data-inset='true'/>
                "
#              when "autocomplete from previous entries" or ""
#                "
#                  <!-- autocomplete='off' disables browser completion -->
#                  <input autocomplete='off' name='#{name}' id='#{question_id}' type='#{question.type()}' value='#{question.value()}'></input>
#                  <ul id='#{question_id}-suggestions' data-role='listview' data-inset='true'/>
#                "
              when "location"
                "
                  <a data-question-id='#{question_id}'>Get current location</a>
                  <label for='#{question_id}-description'>Location Description</label>
                  <input type='text' name='#{name}-description' id='#{question_id}-description'></input>
                  #{
                    _.map(["latitude", "longitude"], (field) ->
                      "<label for='#{question_id}-#{field}'>#{field}</label><input readonly='readonly' type='number' name='#{name}-#{field}' id='#{question_id}-#{field}'></input>"
                    ).join("")
                  }
                  #{
                    _.map(["altitude", "accuracy", "altitudeAccuracy", "heading", "timestamp"], (field) ->
                      "<input type='hidden' name='#{name}-#{field}' id='#{question_id}-#{field}'></input>"
                    ).join("")
                  }
                "

              when "image"
                "<img style='#{question.get "image-style"}' src='#{question.get "image-path"}'/>"
              when "label"
                ""
              else
                "<input name='#{name}' id='#{question_id}' type='#{question.type()}' value='#{question.value()}'></input>"
          }
          </div>
          #{repeatable}
        "
      else
        newGroupId = question_id
        newGroupId = newGroupId + "[0]" if question.repeatable()
        return "<div data-group-id='#{question_id}' class='question group'>" + @toHTMLForm(question.questions(), newGroupId) + "</div>" + repeatable
    ).join("")

  updateCache: ->
    window.questionCache = {}
    window.getValueCache = {}
    window.$questions = $(".question")

    for question in window.$questions
      name = question.getAttribute("data-question-name")
      if name? and name isnt ""
        accessorFunction = {}
        window.questionCache[name] = $(question)
        

        # cache accessor function
        $qC = window.questionCache[name]
        selects = $("select[name=#{name}]", $qC)
        if selects.length is 0
          inputs  = $("input[name=#{name}]", $qC)
          if inputs.length isnt 0
            type = inputs[0].getAttribute("type") 
            isCheckable = type is "radio" or type is "checkbox"
            if isCheckable
              do (name, $qC) -> accessorFunction = -> $("input:checked", $qC).safeVal()
            else
              do (inputs) -> accessorFunction = -> inputs.safeVal()
          else # inputs is 0
            do (name, $qC) -> accessorFunction = -> $(".textarea[name=#{name}]", $qC).safeVal()

        else # selects isnt 0
          do (selects) -> accessorFunction = -> selects.safeVal()

        window.getValueCache[name] = accessorFunction

    window.keyCache = _.keys(questionCache)





  # not used?
  currentKeyExistsInResultsFor: (question) ->
    Coconut.resultCollection.any (result) =>
      @result.get(@key) == result.get(@key) and result.get('question') == question

  repeat: (event) ->
    button = $(event.target)
    newQuestion = button.prev(".question").clone()
    questionID = newQuestion.attr("data-group-id")
    questionID = "" unless questionID?

    # Fix the indexes
    for inputElement in newQuestion.find("input")
      inputElement = $(inputElement)
      name = inputElement.attr("name")
      re = new RegExp("#{questionID}\\[(\\d)\\]")
      newIndex = parseInt(_.last(name.match(re))) + 1
      inputElement.attr("name", name.replace(re,"#{questionID}[#{newIndex}]"))

    button.after(newQuestion.add(button.clone()))
    button.remove()

  getLocation: (event) ->
    question_id = $(event.target).closest("[data-question-id]").attr("data-question-id")
    $("##{question_id}-description").val "Retrieving position, please wait."
    navigator.geolocation.getCurrentPosition(
      (geoposition) =>
        _.each geoposition.coords, (value,key) ->
          $("##{question_id}-#{key}").val(value)
        $("##{question_id}-timestamp").val(moment(geoposition.timestamp).format(Coconut.config.get "date_format"))
        $("##{question_id}-description").val "Success"
        @save()
        $.getJSON "http://api.geonames.org/findNearbyPlaceNameJSON?lat=#{geoposition.coords.latitude}&lng=#{geoposition.coords.longitude}&username=mikeymckay&callback=?", null, (result) =>
          $("##{question_id}-description").val parseFloat(result.geonames[0].distance).toFixed(1) + " km from center of " + result.geonames[0].name
          @save()
      (error) ->
        $("##{question_id}-description").val "Error: #{error}"
      {
        frequency: 1000
        enableHighAccuracy: true
        timeout: 30000
        maximumAge: 0
      }
    )

# jquery helpers

( ($) -> 

  $.fn.scrollTo = (speed = 500, callback) ->
    try
      $('html, body').animate {
        scrollTop: $(@).offset().top + 'px'
        }, speed, null, callback
    catch e
      console.log "error", e
      console.log "Scroll error with 'this'", @

    return @

)($)
