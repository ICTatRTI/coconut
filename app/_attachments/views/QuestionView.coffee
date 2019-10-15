class QuestionView extends Backbone.View

  el : "#content"

  events:
    "change #question-view input"    : "onChange"
    "change #question-view select"   : "onChange"
    "change #question-view textarea" : "onChange"
    "click button.repeat" : "repeat"
    "click button.repeat_summary" : "repeatSummary"
    "click #question-view a:contains(Get current location)" : "getLocation"
    "click .next_error"   : "runValidate"
    "click .validate_one" : "onValidateOne"
    "click .duplicate_update" : "duplicateUpdate"
    "click .duplicate_abordt" : "duplicateAbort"
    "click .duplicate_none" : "duplicateNone"
    "click .remove_repeat" : "removeRepeat"

  initialize: (options) =>
    #window.onbeforeunload = ->
    #  return 'Are you sure you want to quit?'
    (@[key] = value for key, value of options)
    Coconut.resultCollection ?= new ResultCollection()
    @autoscrollTimer = 0
    window.showSuccess = true

    window.duplicateLabels = ['Apellido','Nombre','BarrioComunidad','Año','Día','Mes','Sexo']


  render: =>

    # exit if user name is not defined
    if "module" is Coconut.config.local.get("mode")
      if typeof @standard_values["user_name"] is "undefined"
        alert "Nombre de usuario no está definido."
        return false

    window.skipLogicCache = {}

    questionsName = "<h1>#{@model.id}</h1>" unless "module" is Coconut.config.local.get("mode")



    if "module" is Coconut.config.local.get("mode")
  
      # support for "/" character that might be part of the provider name; it's encoded as "#"
      standard_value_table = "      " + (((->       
        _ref1 = @standard_values
        _results = []
        for key of _ref1
          value = _ref1[key]
          re = new RegExp("#", "g")
          value = value.replace(re, "/")
          _results.push "<input type='hidden' name='" + key + "' value='" + value + "'>"
        _results
      ).call(this)).join("")) + "      "

     @$el.html "
       #{standard_value_table || ''}
       <div style='position:fixed; right:5px; color:white; background-color: #333; padding:20px; display:none; z-index:10: font-size:1.5em !important;' id='messageText'>
        Saving...
       </div>
       #{questionsName || ''}
       <div id='question-view'>
          #{@toHTMLForm(@model)}
       </div>

     "

     @updateCache()

     # for first run
     @updateSkipLogic()
    
     # skipperList is a list of questions that use skip logic in their action on change events
     skipperList = []

     $(@model.get("questions")).each (index, question) =>

       # remember which questions have skip logic in their actionOnChange code 
       skipperList.push(question.safeLabel()) if question.actionOnChange().match(/skip/i)
      
       if question.actionOnQuestionsLoaded() isnt ""
         CoffeeScript.eval question.actionOnQuestionsLoaded()

     #js2form($('#question-view').get(0), @result.toJSON())

     # Trigger a change event for each of the questions that contain skip logic in their actionOnChange code
     @triggerChangeIn skipperList

     @jQueryUIze(@$el)
     #@$el.find('input[type=date]').datebox
     #  mode: "calbox"
     #  dateFormat: "%d-%m-%Y"

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

     $('input, textarea').attr("readonly", "true") if @readonly

     @updateHeightDoc()

     @addUuid()

     surveyName = window.Coconut.questionView.model.id
     @updateLocations() if surveyName is "Participant Registration-es"

     @trigger "rendered"

  renderSummary: =>

    # exit if user name is not defined
    if "module" is Coconut.config.local.get("mode")
      if typeof @standard_values["user_name"] is "undefined"
        alert "Nombre de usuario no está definido."
        return false

    window.skipLogicCache = {}

    questionsName = "<h1>#{@model.id}</h1>" unless "module" is Coconut.config.local.get("mode")



    if "module" is Coconut.config.local.get("mode")

      # support for "/" character that might be part of the provider name; it's encoded as "#"
      standard_value_table = "      " + (((->
        _ref1 = @standard_values
        _results = []
        for key of _ref1
          value = _ref1[key]
          re = new RegExp("#", "g")
          value = value.replace(re, "/")
          _results.push "<input type='hidden' name='" + key + "' value='" + value + "'>"
        _results
      ).call(this)).join("")) + "      "

    @$el.html "
           #{standard_value_table || ''}
           <div style='position:fixed; right:5px; color:white; background-color: #333; padding:20px; display:none; z-index:10: font-size:1.5em !important;' id='messageText'>
            Saving...
           </div>
           #{questionsName || ''}
           <div id='question-view'>
              #{@toSummaryForm(@model)}
           </div>

         "

    #Added support for inactive
    if this.standard_values["isEvaluator"] is "true"
      onClick = "onClick=\"document.location.reload();\""
      @$el.append "<div  style='width:100%; font-size:3em; font-color:red ' id='messageInactive'></div>"
      uuid = Coconut.questionView.result.get("uuid")
      @$("#messageInactive").append "<p align='center'> Por favor proceda con cuidado. Si Clcik el botón de abajo el participante ya no serán accesibles por el sistema.</p><p align='center'><button><a href='#update/inactive/" + uuid + "'>Marcos Inactivo</a></button></p>"


    @updateCache()

    # for first run
    @updateSkipLogic()

    # skipperList is a list of questions that use skip logic in their action on change events
    skipperList = []

    $(@model.get("questions")).each (index, question) =>

      # remember which questions have skip logic in their actionOnChange code
      skipperList.push(question.safeLabel()) if question.actionOnChange().match(/skip/i)

      if question.actionOnQuestionsLoaded() isnt ""
        CoffeeScript.eval question.actionOnQuestionsLoaded()

    #js2form($('#question-view').get(0), @result.toJSON())

    # Trigger a change event for each of the questions that contain skip logic in their actionOnChange code
    @triggerChangeIn skipperList

    @jQueryUIze(@$el)
    #@$el.find('input[type=date]').datebox
    #  mode: "calbox"
    #  dateFormat: "%d-%m-%Y"

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

    $('input, textarea').attr("readonly", "true") if @readonly

    @updateHeightDoc()

    @addUuid()

    surveyName = window.Coconut.questionView.model.id
    @updateLocations() if surveyName is "Participant Registration-es"

    @trigger "rendered"

  jQueryUIze: ( $obj ) ->
     #VBJQUERY 1.9 PORT $obj.find("input[type=text],input[type=number],input[type='autocomplete from previous entries'],input[type='autocomplete from list']").textinput()
     #VBJQUERY 1.9 PORT $obj.find('input[type=radio],input[type=checkbox]').checkboxradio()
     $obj.find("input[type='text'],input[type='number'],input[type='autocomplete from previous entries'],input[type='autocomplete from list']").val()
     $obj.find("input[type='radio'],input[type='checkbox']").checkboxradio()
     $obj.find('ul').listview()
     $obj.find('select').selectmenu()
     $obj.find('a').button()

  addUuid: ->
    if window.questionCache['uuid']
      c = new C32()
      c.getRandom(8)
      c.addChecksum()
      console.log('UUID is' + c.value)
      window.questionCache['uuid'].find("input").val c.value

  triggerChangeIn: ( names ) ->

    for name in names
      elements = []
      elements.push window.questionCache[name].find("input, select, textarea")
      $(elements).each (index, element) =>
        event = target : element
        @actionOnChange event

  saveNewDoc: ( doc ) =>
    newHeight = document.body.scrollHeight
    doc['height'] = newHeight
    $.couch.db("coconut").saveDoc doc

  updateHeightDoc: =>
    heightDocId = "#{@model.id}-height"
    $.couch.db("coconut").openDoc heightDocId,
      success: (doc) =>
        @saveNewDoc doc
      error: (doc) =>
        @saveNewDoc "_id" : heightDocId

  runValidate: -> @validateAll()

  onChange: (event) =>

    event.stopPropagation()
    event.stopImmediatePropagation()

    $target = $(event.target)

    targetName = $target.attr("name")

    if targetName == "Completado"


      if @changedComplete and @isValidAll
        @changedComplete = false
        return

      @validateAll()

      # Update the menu
      # Coconut.menuView.update()
    else
      @changedComplete = false
      messageVisible = window.questionCache[targetName].find(".message").is(":visible")
      warningShowing = window.questionCache[targetName].find(".message .warning").length != 0

      unless messageVisible and not warningShowing
        wasValid = @validateOne
          key: targetName
          autoscroll: false
          button: "<button type='button' data-name='#{targetName}' class='validate_one'>Revisar</button>"

    _.delay =>
      @save()
    , 500

    @updateSkipLogic()
    @actionOnChange(event)

    try 
      messageVisible = window.questionCache[targetName].find(".message").is(":visible")
    catch e
      messageVisible = false
      # do nothing
    @autoscroll(event) if wasValid and not messageVisible

    surveyName = window.Coconut.questionView.model.id
    @duplicateCheck() if surveyName is "Participant Registration-es" and targetName in window.duplicateLabels
    geographyRelevant = ["Provincia","Municipio","BarrioComunidad"].indexOf(targetName) != -1
    @updateLocations() if surveyName is "Participant Registration-es" and geographyRelevant
    



  updateLocations: ->
    _.delay( ->

      PROVINCE = 0
      CITY = 1
      HOOD = 2

      geography = [ ["SANTO DOMINGO","SANTO DOMINGO ESTE","VILLA DUARTE"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","LOS MINA NORTE"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","LOS MINA SUR"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","SANS SOUCI"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","LOS MAMEYES"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","INVIVIENDA"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","LOS TRES OJOS"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","MENDOZA "],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","HAINAMOSA"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","SAN ISIDRO ADENTRO"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","LOS FRAILES"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","LOS MINA VIEJO"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","LA BARQUITA"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","EL DIQUE"],  ["SANTO DOMINGO","SANTO DOMINGO ESTE","BRISAS DEL OZAMA"],  ["SANTO DOMINGO","SANTO DOMINGO OESTE","LAS CAOBAS"],  ["SANTO DOMINGO","SANTO DOMINGO OESTE","BUENOS AIRES DE HERRERA"],  ["SANTO DOMINGO","SANTO DOMINGO OESTE","MANOGUAYABO"],  ["SANTO DOMINGO","SANTO DOMINGO OESTE","BAYONA"],  ["SANTO DOMINGO","SANTO DOMINGO OESTE","EL CAFÉ"],  ["SANTO DOMINGO","SANTO DOMINGO OESTE","LOYOLA"],  ["SANTO DOMINGO","SANTO DOMINGO OESTE","LAS PALMAS DE HERRERA"],  ["SANTO DOMINGO","LOS ALCARRIZOS","LAS MERCEDES"],  ["SANTO DOMINGO","LOS ALCARRIZOS","LOS LIBERTADORES"],  ["SANTO DOMINGO","LOS ALCARRIZOS","ALTOS DE CHAVON"],  ["SANTO DOMINGO","LOS ALCARRIZOS","BARRIO LANDIA"],  ["SANTO DOMINGO","LOS ALCARRIZOS","ZONA FRANCA"],  ["SANTO DOMINGO","SANTO DOMINGO NORTE","VILLA MELLA"],  ["SANTO DOMINGO","SANTO DOMINGO NORTE","SABANA PERDIDA"],  ["SANTO DOMINGO","SANTO DOMINGO NORTE","GUARICANO"],  ["SANTO DOMINGO","SANTO DOMINGO NORTE","LOS CASABES"],  ["SANTO DOMINGO","LA VICTORIA (DM)","LA VICTORIA"],  ["SANTO DOMINGO","BOCA CHICA","ANDRES"],  ["SANTO DOMINGO","BOCA CHICA","LOS COQUITOS"],  ["SANTO DOMINGO","BOCA CHICA","LA COCA"],  ["SANTO DOMINGO","BOCA CHICA","BELLA VISTA"],  ["SANTO DOMINGO","BOCA CHICA","ALTOS DE CHAVON"],  ["SANTO DOMINGO","BOCA CHICA","LA CUEVA DEL HUMO"],  ["SANTO DOMINGO","BOCA CHICA","MONTE REY"],  ["SANTO DOMINGO","BOCA CHICA-LA MALENA","LA MALENA"],  ["SANTO DOMINGO","BOCA CHICA-LA CALETA","LA CIEN MIL - LA PIEDRA"],  ["SANTO DOMINGO","BOCA CHICA-LA CALETA","EL HIGO"],  ["SANTO DOMINGO","BOCA CHICA-LA CALETA","CAMPO LINDO"],  ["SANTO DOMINGO","BOCA CHICA-LA CALETA","LA CALETA"],  ["SANTO DOMINGO","BOCA CHICA-LA CALETA","VALIENTE"],  ["SANTO DOMINGO","BOCA CHICA-LA CALETA","MONTE ADENTRO"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","MARIA ESTELA"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","EL BRISAL"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","MIRAMAR"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","ENSANCHE PROGRESO"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","BRISAS CAUCEDO"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","BARRIO AZUL"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","LOS TANQUECITOS"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","LA BOBINA"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","FINCA VIGIA"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","LOS COCOS"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","MI PROGRESO"],  ["SANTO DOMINGO","BOCA CHICA-ANDRES","BRISAS DEL NORTE (LOS BOTAOS)"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","LA ZURZA"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","VILLAS AGRÍCOLAS"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","VILLA JUANA"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","24 DE ABRIL"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","MEJORAMIENTO SOCIAL"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","VILLA FRANCISCA"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","DOMINGO SAVIO"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","GUALEY"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","SIMON BOLIVAR"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","CAPOTILLO"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","LA CIENAGA"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","LOS GUANDULES"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","LAS CAŇITAS"],  ["DISTRITO NACIONAL","SANTO DOMINGO DE GUZMAN","GUACHUPITA"],  ["SAN CRISTOBAL","VILLA ALTAGRACIA","JUAN PABLO DUARTE"],  ["SAN CRISTOBAL","VILLA ALTAGRACIA","CENTRO DEL PUEBLO"],  ["SAN CRISTOBAL","VILLA ALTAGRACIA","INVI"],  ["SAN CRISTOBAL","BAJOS DE HAINA","PIEDRA BLANCA"],  ["SAN CRISTOBAL","BAJOS DE HAINA","LOS GRINGOS"],  ["SAN CRISTOBAL","BAJOS DE HAINA","EL CENTRO"],  ["MONSEÑOR NOUEL","BONAO","BARRIO PARAISO"],  ["MONSEÑOR NOUEL","BONAO","SANTA ANA"],  ["MONSEÑOR NOUEL","BONAO","BRISAS DEL YUNA"],  ["MONSEÑOR NOUEL","BONAO","VILLA LIBERACION"],  ["MONSEÑOR NOUEL","BONAO","VILLA PROGRESO"],  ["DUARTE","SAN FCO DE MACORIS","VISTA DEL VALLE"],  ["DUARTE","SAN FCO DE MACORIS","CENTRO DE LA CIUDAD-'B'"],  ["DUARTE","SAN FCO DE MACORIS","EL CIRUELILLO"],  ["DUARTE","SAN FCO DE MACORIS","RIVERA DEL JAYA"],  ["DUARTE","SAN FCO DE MACORIS","LOS JARDINES"],  ["DUARTE","SAN FCO DE MACORIS","LOS PISA COSTURA"],  ["DUARTE","SAN FCO DE MACORIS","BUENOS AIRES"],  ["DUARTE","SAN FCO DE MACORIS","SAN VICENTE DE PAUL"],  ["DUARTE","SAN FCO DE MACORIS","SAN MARTIN DE PORRES"],  ["DUARTE","SAN FCO DE MACORIS","EL CAPACITO"],  ["DUARTE","SAN FCO DE MACORIS","EL HORMIGUERO"],  ["DUARTE","SAN FCO DE MACORIS","ERCILIA PEPIN"],  ["DUARTE","SAN FCO DE MACORIS","EL MADRIGAL"],  ["DUARTE","SAN FCO DE MACORIS","LOS GRULLON"],  ["DUARTE","SAN FCO DE MACORIS","UGAMBA"],  ["DUARTE","SAN FCO DE MACORIS","LOS ESPINOLA"],  ["DUARTE","SAN FCO DE MACORIS","SANTA ANA"],  ["DUARTE","SAN FCO DE MACORIS","RABO DE CHIVO"],  ["DUARTE","SAN FCO DE MACORIS","PUEBLO NUEVO"],  ["DUARTE","SAN FCO DE MACORIS","LOS RIELES ABAJO"],  ["DUARTE","SAN FCO DE MACORIS","24 DE ABRIL"],  ["DUARTE","SAN FCO DE MACORIS","LA ALTAGRACIA"],  ["DUARTE","SAN FCO DE MACORIS","CRISTO REY"],  ["DUARTE","SAN FCO DE MACORIS","LAS CAOBAS"],  ["DUARTE","SAN FCO DE MACORIS","27 DE FEBRERO"],  ["DUARTE","SAN FCO DE MACORIS","SAN PEDRO"],  ["DUARTE","SAN FCO DE MACORIS","VILLA VERDE"],  ["DUARTE","SAN FCO DE MACORIS","BARRIO AZUL"],  ["DUARTE","SAN FCO DE MACORIS","AGUAYO"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","HAITI"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","LOS COQUITOS"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","EL JAVILLAR"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","NUEVO RENACER"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","PLAYA OESTE"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","LOS COCOS"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","PADRE GRANERO"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","PADRE LAS CASAS"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","VILLA PROGRESO"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","LOS BORDAS"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","SAN MARCOS"],  ["PUERTO PLATA","SAN FELIPE DE PUERTO PLATA","CRISTO REY"],  ["PUERTO PLATA","IMBERT","CENTRO DEL PUEBLO"],  ["PUERTO PLATA","IMBERT","PROYECTO"],  ["PUERTO PLATA","LUPERON","LUPERON"],  ["PUERTO PLATA","LUPERON","LOS BELLOSOS"],  ["PUERTO PLATA","LUPERON","LOS RAMONES"],  ["PUERTO PLATA","SOSUA","SAN ANTONIO"],  ["PUERTO PLATA","SOSUA","LOS CHARAMICOS"],  ["PUERTO PLATA","SOSUA","EL BATEY"],  ["PUERTO PLATA","SOSUA","SOSUA ABAJO"],  ["PUERTO PLATA","SOSUA","MARANATHA"],  ["PUERTO PLATA","SOSUA","LOS CASTILLOS"],  ["PUERTO PLATA","CABARETE","CABARETE"],  ["PUERTO PLATA","CABARETE","CALLEJON DE LA LOMA"],  ["PUERTO PLATA","CABARETE","CALLEJON DEL BLANCO"],  ["PUERTO PLATA","CABARETE","BOMBITA"],  ["PUERTO PLATA","CABARETE","LAS CIENAGAS"],  ["PUERTO PLATA","MONTELLANO","LOS CIRUELOS"],  ["PUERTO PLATA","MONTELLANO","PANCHO MATEO"],  ["PUERTO PLATA","MONTELLANO","EL TAMARINDO"],  ["PUERTO PLATA","MONTELLANO","SEBERET"],  ["PUERTO PLATA","MONTELLANO","LOS CARTONES"],  ["SANTIAGO","VILLA BISONO (NAVARRETE)","EL CERRO"],  ["SANTIAGO","VILLA BISONO (NAVARRETE)","CENTRO DEL PUEBLO"],  ["SANTIAGO","VILLA BISONO (NAVARRETE)","LOS CANDELONES"],  ["SANTIAGO","VILLA BISONO (NAVARRETE)","DUARTE"],  ["SANTIAGO","LICEY AL MEDIO","LICEY AL MEDIO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","GUAYABAL AL MEDIO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","PUÑAL ADENTRO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","CIENFUEGOS"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","MONTE RICO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LOS SALADOS"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","ALTOS DE VIREYA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","ESPAILLAT"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","BERMUDEZ"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LA OTRA BANDA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","BARACOA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LA JOYA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","BELLA VISTA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LA YAGUITA DE PASTOR"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LOS JAZMINES"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","NIBAJE"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LOS PEPINES"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","BUENOS AIRES"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","HATO DEL YAQUE"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","CAMBOYA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LOS CIRUELITOS"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","EL HOYO DE LIA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LA CANELA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","PEKIN"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","ARROYO HONDO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","TAMBORIL"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","GURABO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","SABANA IGLESIA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","EL EJIDO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","ENSANCHE HERMANAS MIRABAL"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LOS PLATANITOS"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","GURABITO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","BARRIO LINDO"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","LA HERREDURA"],  ["SANTIAGO","SANTIAGO DE LOS CABALLEROS","HATO MAYOR"] ]

      $province = questionCache.Provincia.find("input")
      $city = questionCache.Municipio.find("input")
      $hood = questionCache.BarrioComunidad.find("input")

      provinces = []
      cities = []
      hoods = []

      # make appropriate lists
      for location in geography
        provinces.push(location[PROVINCE]) unless ~provinces.indexOf(location[PROVINCE])
        if $province.val().toLowerCase() is location[PROVINCE].toLowerCase()
          cities.push(location[CITY])unless ~cities.indexOf(location[CITY])
        if $city.val().toLowerCase() is location[CITY].toLowerCase()
          hoods.push(location[HOOD])unless ~hoods.indexOf(location[HOOD])

      todo = [
        [$province, provinces]
        [$city, cities]
        [$hood, hoods]
      ]

      # update autofill options
      $(todo).each (index, data) ->
        element = data[0]
        list    = data[1]
        element.autocomplete
          source: list
          minLength: 1
          target: "##{element.attr("id")}-suggestions"
          callback: (event) ->
            element.val($(event.currentTarget).text())
            element.autocomplete('clear')
    , 1000)


  duplicateCheck: (event) ->
    count = 0

    window.Coconut.duplicates = []

    for label in window.duplicateLabels
      count++ if window.getValueCache[label]?()

    spacePattern = new RegExp(" ", "g")

    family    = (window.getValueCache['Apellido']()        || '').toLowerCase().replace(spacePattern, '')
    names     = (window.getValueCache['Nombre']()          || '').toLowerCase().replace(spacePattern, '')
    municipality = (window.getValueCache['Municipio']() || '').toLowerCase().replace(spacePattern, '')
    community = (window.getValueCache['BarrioComunidad']() || '').toLowerCase().replace(spacePattern, '')
    sexo      = (window.getValueCache['Sexo']()            || '').toLowerCase().replace(spacePattern, '')
    dobDia      = (window.getValueCache['Día']()            || '').toLowerCase().replace(spacePattern, '')
    #ensure Dia is always 2 characters so it matches the view, e.g. 01,02, etc..
    dobDia = "0" + dobDia  if dobDia.length < 2

    dobMes      = (window.getValueCache['Mes']()            || '').toLowerCase().replace(spacePattern, '')
    dobAno      = (window.getValueCache['Año']()            || '').toLowerCase().replace(spacePattern, '')

    mesNum = spanishMonth2Number(dobMes)
    mesNum = "0" + mesNum  if mesNum.length < 2
    key = [family, names, municipality, community, sexo, dobDia, mesNum, dobAno].join(":")


    return if ~key.indexOf("::")

    window.Coconut.duplicateKeys = {}

    $.couch.db("coconut").view "coconut/duplicateCheck", 
      keys: [key]
      success: (data) ->

        ignoredKeys = "_rev _id question collection".split(" ")

        return if data.rows.length is 0

        $("#content").append "<div id='duplicates'></div>" if $("#duplicates").length is 0

        alert "Duplicados posibles detectado"

        html = "<br><br>
          <h1>Duplicados posibles</h1>
        "

        for row, i in data.rows

          window.Coconut.duplicateKeys[row.key] = true
          window.Coconut.duplicates[i] = row.value

          html += "
            <h2>Posibilidad #{i+1}</h2>
            <table style='font-size: 1.4em;'>
              <tr>
          "
          for key, value of row.value
            html += "<tr><th style='text-align:left;'>#{key}</th><td>#{value}</td></tr>" if value? and not ~ignoredKeys.indexOf(key)

          html += "
              </tr>
              <tr>
                <td colspan='2' style='font-size:1.5em; padding:1em;'>
                  Si esta persona es una duplicada,<br>
                  <button class='duplicate_update' data-index='#{i}'>Usar esta informaci&oacute;n y actualizar</button><br>
                  <button class='duplicate_abort' data-index='#{i}'>Abortar corriente impreso</button>
                </td>
              </tr>
            </table>
          "

        html += "
          <button class='duplicate_none'>No hay duplicados. Clarar.</button>
        "

        $("#duplicates").html html

        $("#duplicates").scrollTo()

  duplicateUpdate: ( event ) =>
    event.stopImmediatePropagation()
    if confirm "Reemplazar corriente información con esta?"
      index = parseInt($(event.target).attr("data-index"))
      questionCache['uuid'].find("input").val(window.Coconut.duplicates[index]['uuid'])
      js2form($('#question-view').get(0), window.Coconut.duplicates[index])
      $("#duplicates").empty()

  duplicateAbort: (event) =>
    event.stopImmediatePropagation()
    window.location.reload() if confirm("¿Está seguro?\n\nEste acción caminará un impreso nuevo.")

  duplicateNone: =>
    $("#duplicates").empty()

  onValidateOne: (event) -> 
    $target = $(event.target)
    name = $(event.target).attr('data-name')
    @validateOne
      key : name
      autoscroll: true
      leaveMessage : false
      button : "<button type='button' data-name='#{name}' class='validate_one'>Revisar</button>"

  validateAll: () ->

    $button = $("[name=Completado]")
    @isValidAll = false
    
   
    isValid = true

    for key in window.keyCache

      $question = window.questionCache[key]

      if not $question.hasClass("group")
      
          questionIsntValid = not @validateOne 
            key          : key
            autoscroll   : isValid
            leaveMessage : false
      

      if isValid and questionIsntValid
        isValid = false
        #alert("La forma no puede ser completa debido a un error. Por favor, volver atrás y corregir sus errores.")


    @completeButton isValid

    # find the complete button
    completeButtonModel = _(Coconut.questionView.model.get("questions")).filter((a) -> a.get("label") == "Completado" )[0]

    hasOnComplete = completeButtonModel.has("onComplete")

    onComplete = completeButtonModel.get("onComplete") if hasOnComplete

    if hasOnComplete and isValid
      #TBD: find a better way to distinguish b/n registration and survey forms



      switch onComplete.type
        when "redirect"
          # requirement for redirect
          if onComplete.route? 
            Coconut.router.navigate onComplete.route, true
        when "choice"
          # requirements for choice
          if onComplete.message? and onComplete.links?
            html = "<p>#{onComplete.message}</p>"
            for link in onComplete.links
              aPassed = []

              # pass any varaibles needed
              if link.pass?
                for key in link.pass
                  aPassed.push "#{key}=#{window.getValueCache[key]()}"
                
              if link.urlparams?
                for key in link.urlparams
                  re = new RegExp("/", "g")
                  v = $('[name="' + key + '"]').val()
                  v = v.replace(re, "#")
                  #aPassed.push "#{key}=#{$('[name="' + key + '"]').val()}"
                  aPassed.push "#{key}=#{v}"


              if aPassed.length > 0
                if link.isreport?
                  sPassed = "/" + aPassed.join("/")
                else
                  sPassed = "/" + aPassed.join("&")



              # handle going to the same route
              onClick = ''
              if not link.pass? and ~window.location.href.indexOf(link.route)
                onClick = 'onClick=\"document.location.reload();\"'
              

              html += "<button><a href='##{link.route}#{sPassed || ''}' #{onClick}>#{link.label}</a></button>"
            $button.after("<div class='onComplete'>#{html}</div>") unless $(".onComplete").length != 0
            $(".onComplete").scrollTo()

    $button.scrollTo() if isValid


    @isValidAll = true if isValid

    return isValid


  validateOne: ( options ) ->

    key          = options.key          || ''
    autoscroll   = options.autoscroll   || false
    button       = options.button       || "<button type='button' class='next_error'>Siguiente Error</button>"
    leaveMessage = options.leaveMessage || false

    $question = window.questionCache[key]
    $message  = $question.find(".message")


    return '' if key is 'Completado'
    return '' if $question.hasClass("group")

    try
      message = @isValid(key)
    catch e
      alert "isValid error in #{key}\n#{e}"
      message = ""

    if $message.is(":visible") and leaveMessage
      if message is "" then return true else return false

    warning = @getWarning(key)

    if message is "" and warning is "" # nothing to show
      $message.hide()
      if autoscroll
        @autoscroll $question
      return true
    else if message is "" and warning isnt "" # only warning to show
      warning = "<span class='warning'>#{warning}</span>"
      $message.show().html(warning)
      return true
    else if message isnt "" and warning is "" # only message to show
      $message.show().html("
        #{message}
        #{button}
      ").find("button").button()
      return false
    else
      warning = "<span class='warning'>#{warning}</span>"
      $message.show().html("
        #{message}
        #{warning}
        #{button}
      ").find("button").button()
      return false


  isValid: ( question_id ) ->

    return unless question_id
    result = []

    questionWrapper = window.questionCache[question_id]
    
    # early exit, don't validate labels
    return "" if questionWrapper.hasClass("label")

    question        = $("[name='#{question_id}']", questionWrapper)

    type            = $(questionWrapper.find("input").get(0)).attr("type")
    labelText       = 
      if type is "radio"
        $("label[for=#{question.attr("id").split("-")[0]}]", questionWrapper).text() || ""
      else
        $("label[for=#{question.attr("id")}]", questionWrapper)?.text()
    required        = questionWrapper.attr("data-required") is "true"
    required        = false if type is "checkbox"

    validation      = unescape(questionWrapper.attr("data-validation"))
    validation      = null if validation is "undefined"

    value           = window.getValueCache[question_id]()

    #
    # Exit early conditions
    #

    # don't evaluate anything that's been skipped. Skipped = valid
    return "" if not questionWrapper.is(":visible")
    
    # "" = true
    return "" if question.find("input").length != 0 and (type == "checkbox" or type == "radio")

    result.push "'#{labelText}' se requiere." if required and ( value is "" or value is null )

    if validation? && validation isnt ""

      try
        validationFunctionResult = (CoffeeScript.eval("(value) -> #{validation}", {bare:true}))(value)
        result.push validationFunctionResult if validationFunctionResult?
      catch error
        return '' if error == 'invisible reference'
        alert "Validation error for #{question_id} with value #{value}: #{error}"


    if result.length isnt 0
      return result.join("<br>") + "<br>"

    return ""

  getWarning: ( question_id ) ->

    value           = window.getValueCache[question_id]()
    questionWrapper = window.questionCache[question_id]
    question        = $("[name='#{question_id}']", questionWrapper)
    warningCode     = unescape(questionWrapper.attr("data-warning"))

    if warningCode? && warningCode isnt ""
      try
        warningFunctionResult = (CoffeeScript.eval("(value) -> #{warningCode}", {bare:true}))(value)
        return warningFunctionResult if warningFunctionResult?
      catch error
        return '' if error == 'invisible reference'
        alert "Custom warning error for #{question_id} with value #{value}: #{error}"
    return ''


  autoscroll: (event) ->

    clearTimeout @autoscrollTimer

    if event.jquery
      $div = event
      name = $div.attr("data-question-name")
    else
      $target = $(event.target)
      name = $target.attr("name")
      $div = window.questionCache[name]

    return if $div.hasClass "checkbox"

    $oldNext = $div
    @$next = $div.next(".question")
    @$prev = $div

    if @$next.length is 0 # if nothing, check parents
      $parentsMaybe = $oldNext.parent().next(".question")
      if $parentsMaybe.length isnt 0
        @$next = $parentsMaybe


    count = 0

    if not @$next.is(":visible")

      while (not @$next.is(":visible")) or @$next.length isnt 0
        count++
        $oldNext = $(@$next)
        @$next = @$next.next(".question")
        break if count > 100
        # if run out, check parents
        if @$next.length is 0
          $parentsMaybe = $oldNext.parent().next(".question")
          if $parentsMaybe.length isnt 0
            @$next = $parentsMaybe

    else
      $(window).on( "scroll", => $(window).off("scroll"); clearTimeout @autoscrollTimer;)
      @autoscrollTimer = setTimeout(
        => 
          $(window).off( "scroll" )
          #VBJQUERY @$next.scrollTo().find("input[type='text'],input[type='number'],input[type='autocomplete from previous entries'], input=[type='autocomplete from list']").first().focus()
          if not window.saveERROR
            @$next.scrollTo().find("input[type='text'],input[type='number'],input[type='autocomplete from previous entries'], input[type='autocomplete from list']").first().focus()
          else
            _this.$prev.scrollTo().find("input[type='text'],input[type='number'],input[type='autocomplete from previous entries'], input[type='autocomplete from list']").first().val('');
            @$prev.scrollTo().find("input[type='text'],input[type='number'],input[type='autocomplete from previous entries'], input[type='autocomplete from list']").first().focus()

        1000
      )

  # takes an event as an argument, and looks for an input, select or textarea inside the target of that event.
  # Runs the change code associated with that question.
  actionOnChange: (event) ->

    nodeName = $(event.target).get(0).nodeName
    $target = 
      if nodeName is "INPUT" or nodeName is "SELECT" or nodeName is "TEXTAREA"
        $(event.target)
      else
        $(event.target).parent().parent().parent().find("input,textarea,select")

    name = $target.attr("name")
    $divQuestion = $(".question [data-question-name='#{name}']")
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

    for name, $question of window.questionCache

      skipLogicCode = window.skipLogicCache[name]
      continue if skipLogicCode is "" or not skipLogicCode?

      try
        result = eval(skipLogicCode)

      catch error
        if error == "invisible reference"
          result = true
        else
          name = ((/function (.{1,})\(/).exec(error.constructor.toString())[1])
          message = error.message
          alert "Skip logic error in question #{$question.attr('data-question-id')}\n\n#{name}\n\n#{message}"

      if result
        $question[0].style.display = "none"
      else
        $question[0].style.display = ""



  # We throttle to limit how fast save can be repeatedly called
  save: _.throttle( =>
      currentData = $('#question-view').toObject(skipEmpty: false)

      # Make sure lastModifiedAt is always updated on save
      currentData.lastModifiedAt = moment(new Date()).format(Coconut.config.get "datetime_format")
      currentData.savedBy = $.cookie('current_user')
      Coconut.questionView.result.save currentData,
        success: ->
          window.saveERROR = false

          if  Coconut.questionView.result.get("Completado") is "true"

            uuid = Coconut.questionView.result.get("uuid")

            if window.Coconut.questionView.model.id == 'Participant Registration-es' and  window.showSuccess
              alert('¡Enhorabuena! Has completado correctamente el formulario. El UUID para esta forma es "'+ uuid + '". Tome nota de este número para referencia futura.');
              window.showSuccess = false

            else if window.showSuccess and window.Coconut.questionView.model.id == 'Participant Survey-es' and  window.showSuccess
              alert('¡Felicitaciones! Has completado la Encuesta de Participante para UUID=' + uuid);
              window.showSuccess = false


          $("#messageText").slideDown().fadeOut()
        error: ->
          window.saveERROR = true
          if window.showSuccess
            alert 'La información digitada no fue guardada debido a un problema con su conexión de Internet. Por favor, verifique y corriga su conexión de internet antes de continuar con la digitación'

    , 1000, trailing: false )

  completeButton: ( value ) ->
    @changedComplete = true
    if $('[name=Completado]').prop("checked") isnt value
      $('[name=Completado]').click()
      @save()

  toHTMLForm: (questions = @model, groupId, isRepeatedGroup, index) ->
    # Need this because we have recursion later
    questions = [questions] unless questions.length?
    unless index?
      index = 0
    else
      if isRepeatedGroup
        titleIndex = "<span class='title_index'>#{index+1}</span>"

    html = ''

    _(questions).each (question) =>

      labelHeader = if question.type() is "label"
        ["<h2>","</h2>"]
      else
        ["", ""]

      warning = "
        data-warning='#{_.escape(question.warning())}'
      " if question.has('warning')

      validation = "
        data-validation='#{_.escape(question.validation())}'
      " if question.has('validation')

      isRepeatable = question.repeatable()

      repeatButton = "
        <button class='repeat'>+</button>
      " if isRepeatable

      if isRepeatable || isRepeatedGroup
        name        = question.safeLabel() + "[#{index}]"
        question_id = question.get("id") + "-#{index}"
      else
        name        = question.safeLabel()
        question_id = question.get("id")

      window.skipLogicCache[name] =
        if question.skipLogic() isnt ''
          CoffeeScript.compile(question.skipLogic(),bare:true)
        else
          ''

      if question.questions().length isnt 0

        groupTitle = "<h1>#{question.label()} #{titleIndex || ''}</h1>" if question.label() isnt '' and question.label() isnt question.get("_id")

        html += "
          <div 
            data-group-id='#{question_id}'
            data-question-name='#{name}'
            data-question-id='#{question_id}'
            class='question group'>
            #{(groupTitle) || ''}
            #{@toHTMLForm(question.questions(), question_id, isRepeatable, index)}
          </div>

          #{repeatButton || ''}

        "
      else
        html += "
          <div
            #{("style='display:none;'" if question.type() is 'hidden') || ''}
            class='question #{question.type()}'

            data-question-name='#{name}'
            data-question-id='#{question_id}'
            data-action_on_change='#{_.escape(question.actionOnChange())}'

            #{validation || ''}
            #{warning    || ''}
            data-required='#{question.required()}'
          >

          #{
          unless question.type() is 'hidden'
            "<label type='#{question.type()}' for='#{question_id}' class='ui-input-text'>#{labelHeader[0]}#{question.label()}#{labelHeader[1]} <span></span></label>"
          else
            ""
          }
          #{"<p class='grey'>#{question.hint()}</p>"}
          <div class='message'></div>
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
                  "<input name='#{name}' type='text' id='#{question_id}' value='#{question.value()}' class='ui-input-text ui-body-c ui-corner-all ui-shadow-inset'></input>"
                else
                  options = question.get("radio-options")
                  _.map(options.split(/, */), (option,index) ->
                    "
                      <label for='#{question_id}-#{index}' class='ui-input-text'>#{option}</label>
                      <input type='radio' name='#{name}' id='#{question_id}-#{index}' value='#{_.escape(option)}'/>
                    "
                  ).join("")
              when "programnameslist"
                programString = ''
                programString += "
                                  <div
                                    data-group-id='#{question_id}'
                                    data-question-name='#{name}'
                                    data-question-id='#{question_id}'
                                    class='question group'>
                                  "

                for program in this.wsData.programsList
                  questionProgramId = question_id + '_' + program.programnameid
                  questionProgramName = name + '_' + program.programnameid
                  programString += "
                                      <div
                                        class='question radio'

                                        data-question-name='#{questionProgramName}'
                                        data-question-id='#{questionProgramId}'
                                        data-action_on_change='#{_.escape(question.actionOnChange())}'

                                        #{validation || ''}
                                        #{warning    || ''}
                                        data-required='true'
                                      >"
                  programString += "<h2>#{program.programname}</h2>"
                  if @readonly
                    programString += "<input name='#{questionProgramName}' type='text' id='#{questionProgramId}' value='#{question.value()}' class='ui-input-text ui-body-c ui-corner-all ui-shadow-inset'></input>"
                  else
                    options = question.get("radio-options")
                    programString += _.map(options.split(/, */), (option,index) ->
                      "
                                              <label for='#{questionProgramId}-#{index}' class='ui-input-text'>#{option}</label>
                                              <input type='radio' name='#{questionProgramName}' id='#{questionProgramId}-#{index}' value='#{_.escape(option)}'/>
                                            "
                    ).join("")
                  programString += "</div>"


                programString += "</div>"
                programString
              when "date"
                if @readonly
                  "<input name='#{name}' type='text' id='#{question_id}' value='#{question.value()}'>"
                else
                  "
                    <br>
                    <input type='date' name='#{name}' id='#{question_id}' class='ui-input-text' value='#{_.escape(option)}'/>
                  "
              when "checkbox"
                if @readonly
                  "<input name='#{name}' type='text' id='#{question_id}' value='#{_.escape(question.value())}'></input>"
                else
                  "<input style='display:none' name='#{name}' id='#{question_id}' type='checkbox' value='true'></input>"
              when "checkboxpreselected"
                if @readonly
                  "<input name='#{name}' type='text' id='#{question_id}' value='#{_.escape(question.value())}'></input>"
                else
                  cbChecked = ""
                  cbValue = this.result.safeGet(name, '')
                  if cbValue in ['true']
                    cbChecked = " checked='checked' "
                  "<input style='display:none' name='#{name}' id='#{question_id}' type='checkbox' value='true' #{cbChecked}></input>"
              when "autocomplete from list", "autocomplete from previous entries"
                "
                  <!-- autocomplete='off' disables browser completion -->
                  <input autocomplete='off' name='#{name}' id='#{question_id}' type='#{question.type()}' value='#{question.value()}' data-autocomplete-options='#{question.get("autocomplete-options")}' class='ui-input-text ui-body-c ui-corner-all ui-shadow-inset'></input>
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
                  <label for='#{question_id}-description' class='ui-input-text'>Location Description</label>
                  <input type='text' name='#{name}-description' id='#{question_id}-description'></input>
                  #{
                    _.map(["latitude", "longitude"], (field) ->
                      "<label for='#{question_id}-#{field}' class='ui-input-text'>#{field}</label><input readonly='readonly' type='number' name='#{name}-#{field}' id='#{question_id}-#{field}'></input>"
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
              when "hidden"
                unless @readonly
                  "<input type='hidden' name='#{name}' id='#{question_id}'>"
                else
                  "<input name='#{name}' type='text' id='#{question_id}' value='#{_.escape(question.value())}'>"
              when "label"
                ""
              else
                "<input name='#{name}' id='#{question_id}' type='#{question.type()}' value='#{question.value()}' class='ui-input-text ui-body-c ui-corner-all ui-shadow-inset'></input>"
          }
          </div>
          #{repeatButton || ''}
        "

    return html

  toSummaryForm: (questions = @model, groupId, isRepeatedGroup, index) ->
    # Need this because we have recursion later
    questions = [questions] unless questions.length?
    unless index?
      index = 0
    else
      if isRepeatedGroup
        titleIndex = "<span class='title_index'>#{index+1}</span>"

    html = ''

    _(questions).each (question) =>

      labelHeader = if question.type() is "label"
        ["<h2>","</h2>"]
      else
        ["", ""]

      warning = "
        data-warning='#{_.escape(question.warning())}'
      " if question.has('warning')

      validation = "
        data-validation='#{_.escape(question.validation())}'
      " if question.has('validation')

      isRepeatable = question.repeatable()

      repeatButton = "
        <button class='repeat_summary'>+</button>
      " if isRepeatable

      if isRepeatable || isRepeatedGroup
        name        = question.safeLabel() + "[#{index}]"
        question_id = question.get("id") + "-#{index}"
      else
        name        = question.safeLabel()
        question_id = question.get("id")

      window.skipLogicCache[name] =
        if question.skipLogic() isnt ''
          CoffeeScript.compile(question.skipLogic(),bare:true)
        else
          ''

      if question.questions().length isnt 0

        groupTitle = "<h1>#{question.label()} #{titleIndex || ''}</h1>" if question.label() isnt '' and question.label() isnt question.get("_id")

        html += "
          <div 
            data-group-id='#{question_id}'
            data-question-name='#{name}'
            data-question-id='#{question_id}'
            class='question group'>
            #{(groupTitle) || ''}
            #{@toSummaryForm(question.questions(), question_id, isRepeatable, index)}
          </div>

          #{repeatButton || ''}

        "
      else
        html += "
          <div
            #{("style='display:none;'" if question.type() is 'hidden') || ''}
            class='question #{question.type()}'

            data-question-name='#{name}'
            data-question-id='#{question_id}'
            data-action_on_change='#{_.escape(question.actionOnChange())}'

            #{validation || ''}
            #{warning    || ''}
            data-required='#{question.required()}'
          >

          #{
          unless question.type() is 'hidden'
            "<label type='#{question.type()}' for='#{question_id}' class='ui-input-text'>#{labelHeader[0]}#{question.label()}#{labelHeader[1]} <span></span></label>"
          else
            ""
          }
          #{"<p class='grey'>#{question.hint()}</p>"}
          <div class='message'></div>
          #{
            switch question.type()
              when "textarea"
                "#{this.result.safeGet(name, '')}"
              when "select"
                if @readonly
                  question.value()
                else

                  html = "<select>"
                  for option, index in question.get("select-options").split(/, */)
                    html += "<option name='#{name}' id='#{question_id}-#{index}' value='#{option}'>#{option}</option>"
                  html += "</select>"
              when "radio"
                "#{this.result.safeGet(name, '')}"
              when "date"
                "#{this.result.safeGet(name, '')}"
              when "checkbox"
                cbChecked = ""
                cbValue = this.result.safeGet(name, '')
                if cbValue in ['true']
                  cbChecked = " checked='checked' "

                "<input style='display:none' name='#{name}' id='#{question_id}' type='checkbox' value='true' #{cbChecked} disabled='disabled'></input>"
              when "autocomplete from list", "autocomplete from previous entries"
                "#{this.result.safeGet(name, '')}"
              when "location"
                "
                  <a data-question-id='#{question_id}'>Get current location</a>
                  <label for='#{question_id}-description'>Location Description</label>
                  <input type='text' name='#{name}-description' id='#{question_id}-description'></input>
                  #{
                    _.map(["latitude", "longitude"], (field) ->
                      "<label for='#{question_id}-#{field}' class='ui-input-text'>#{field}</label><input readonly='readonly' type='number' name='#{name}-#{field}' id='#{question_id}-#{field}'></input>"
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
              when "hidden"
                unless @readonly
                  "<input type='hidden' name='#{name}' id='#{question_id}'>"
                else
                  "<input name='#{name}' type='text' id='#{question_id}' value='#{_.escape(question.value())}'>"
              when "label"
                ""
              else
                "#{this.result.safeGet(name, '')}"

          }
          </div>
          #{repeatButton || ''}
        "

    return html



  updateCache: ->
    window.questionCache = {}
    window.getValueCache = {}
    window.$questions = $(".question")


    for question in window.$questions
      name = question.getAttribute("data-question-name")
      continue if name is "Completado"
      continue if name is Coconut.questionView.model.safeLabel()
      if name? and name isnt ""
        accessorFunction = {}
        window.questionCache[name] = $(question)

        # cache accessor function
        $qC = window.questionCache[name]
        selects = $("select[name='#{name}']", $qC)
        if selects.length is 0
          inputs  = $("input[name='#{name}']", $qC)
          if inputs.length isnt 0
            type = inputs[0].getAttribute("type") 
            isCheckable = type is "radio" or type is "checkbox"
            if isCheckable
              do (name, $qC) -> accessorFunction = -> $("input:checked", $qC).safeVal()
            else
              if type is "hidden"
                do (inputs) -> accessorFunction = -> inputs.val()
              else
                do (inputs) -> accessorFunction = -> inputs.safeVal()
          else # inputs is 0
            do (name, $qC) -> accessorFunction = -> $(".textarea[name='#{name}']", $qC).safeVal()

        else # selects isnt 0
          do (selects) -> accessorFunction = -> selects.safeVal()

        window.getValueCache[name] = accessorFunction

    window.keyCache = _.keys(questionCache)

  # not used?
  currentKeyExistsInResultsFor: (question) ->
    Coconut.resultCollection.any (result) =>
      @result.get(@key) == result.get(@key) and result.get('question') == question

  repeat: (event) ->

    event.stopImmediatePropagation()

    $button   = $(event.target)
    $question = $button.prev(".question")

    idSplit = $question.attr("data-question-id").split("-")

    id      = parseInt(_(idSplit).first())
    index   = parseInt(_(idSplit).last())

    question = _(Coconut.questionView.model.questions()).where({"id":id})[0]

    groupId = ''
    isRepeatedGroup = true

    # render html and make a dom fragment
    $el = $(@toHTMLForm(question, groupId, isRepeatedGroup, index + 1))

    # add dom fragment after question being duplicated
    $question.after($el)

    # add delete button
    $el.find(".question").last().append("<button class='remove_repeat'>Borrar</button><br>") if $el.find(".remove_repeat").length == 0

    # call jquery on new section
    @jQueryUIze($el)

    # remove duplicate button
    $button.remove()

    Coconut.questionView.updateCache()


  repeatSummary: (event) ->

    event.stopImmediatePropagation()

    $button   = $(event.target)
    $question = $button.prev(".question")

    idSplit = $question.attr("data-question-id").split("-")

    id      = parseInt(_(idSplit).first())
    index   = parseInt(_(idSplit).last())

    question = _(Coconut.questionView.model.questions()).where({"id":id})[0]

    groupId = ''
    isRepeatedGroup = true

    # render html and make a dom fragment
    $el = $(@toSummaryForm(question, groupId, isRepeatedGroup, index))

    # add dom fragment after question being duplicated
    $question.after($el)

    # add delete button
    $el.find(".question").last().append("<button class='remove_repeat'>Borrar</button><br>") if $el.find(".remove_repeat").length == 0

    # call jquery on new section
    @jQueryUIze($el)

    # remove duplicate button
    $button.remove()

    Coconut.questionView.updateCache()


  removeRepeat: (event) ->
    $parent = $(event.target).parent()
    i = 0
    while not $parent.hasClass("group")
      break if i++ > 50
      $parent = $parent.parent()

    $parent.remove()


  getLocation: (event) ->
    question_id = $(event.target).closest("[data-question-id]").attr("data-question-id")
    $("##{question_id}-description").val "Retrieving position, please wait."
    navigator.geolocation.getCurrentPosition(
      (geoposition) =>
        _.each geoposition.coords, (value,key) ->
          $("##{question_id}-#{key}").val(value)
        $("##{question_id}-timestamp").val(moment(geoposition.timestamp).format(Coconut.config.get "datetime_format"))
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

# other helpers

window.SkipTheseWhen = ( argQuestions, result ) ->
  questions = []
  argQuestions = argQuestions.split(/\s*,\s*/)
  for question in argQuestions
    questions.push window.questionCache[question]
  disabledClass = "disabled_skipped"

  for question in questions
    if result
      question.addClass disabledClass
    else
      question.removeClass disabledClass

window.ResultOfQuestion = ( name ) -> return window.getValueCache[name]?() || null

