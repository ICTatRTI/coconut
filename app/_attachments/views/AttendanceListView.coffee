class AttendanceListView extends Backbone.View

  el: "#content"

  #events:
   #"click #searchButton" : "filter"

  initialize: (options) ->
    (@[key] = value for key, value of options)
    Coconut.resultCollection ?= new ResultCollectionWithCollateral()


  filter: (event) ->
    query = @$el.find("#search").val()
    table = @$el.find(".tablesorter")
    rows = $(".tablesorter tr")
    for id, row of @searchRows
      foundRow = this.findTRByClass(rows, id)
      if foundRow != null
        if ~row.indexOf(query) or query.length < 3
          $(foundRow).show()
          #          @$el.find(".row-#{escapedId}").show()
        else
          $(foundRow).hide()
  #          @$el.find(".row-#{escapedId}").hide()



  findTRByClass: (rows, className) ->
     for id, row of rows
       classNames = row.className
       if classNames.indexOf(className) > -1
        return row
     return null

  save: ->
    currentData = $('#attendanceForm').toObject(skipEmpty: true)
    # Check if uuid was unchecked and initialize it to false if any like that found.
    # This is to fix the bug where unchecked were not unchecked.
    i = 0
    while i < Coconut.attendanceListView.initialCheckedUUIDs.length
      initialUUID = Coconut.attendanceListView.initialCheckedUUIDs[i]
      if !currentData.hasOwnProperty(initialUUID)
        currentData[initialUUID] = 'false'
      i++

    # Make sure lastModifiedAt is always updated on save
    currentData.lastModifiedAt = moment(new Date()).format(Coconut.config.get "datetime_format")
    currentData.savedBy = $.cookie('current_user')
    Coconut.attendanceListView.result.save currentData,
      success: ->
        $("#content").html("<p align='center' style='font-size:12pt'>
        Lista de asistentes se ha guardado.</p>")

  render: ->

    @searchRows = {}

    html = ""
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

    html += "#{standard_value_table || ''}<div style='font-size: 14pt;font-weight: bold'>" + @standard_values.activity_name + "</div><br>"
    #html += "<div style='font-size: 10pt'><input type='text' id='search' placeholder='filter'></div><br>";
    html += "<div id='attendanceForm' style='overflow:auto;'><table id='participants'>
          <thead>
            <tr>
              <th></th>
              <th>UUID</th>
              <th>Fecha de Creación</th>
              <th>Apellido</th>
              <th>Nombre</th>
              <th>Sexo</th>
              <th>Fecha de <br/>Nacimiento</th>
              <th>Barrio o Sector</th>
              <th>Teléfono</th>
              <th>Es Colateral</th>
              <th>Facebook</th>
            </tr></thead>
        <tbody>"

    participantsSorted = caseInsensitiveSortJSONData @wsData.participants.rows, "Apellido", true

    Coconut.attendanceListView.initialCheckedUUIDs = []

    for participant in participantsSorted
      participantData = participant.value
      html += "<tr class='row-#{participantData.uuid}'>"
      @searchRows[participantData.uuid] = ""
#      regvals = jQuery.parseJSON(participant.value)
      cbChecked = ""
      cbValue = this.result.safeGet(participantData.uuid, '')
      if cbValue in ['true']
        Coconut.attendanceListView.initialCheckedUUIDs.push participantData.uuid
        cbChecked = " checked='checked' "

      cbHTML = "<input name='#{participantData.uuid}' id='#{participantData.uuid}' type='checkbox' value='true' #{cbChecked}></input>"

      html += "<td>" + cbHTML + "</td>"

      html += @createColumn(participantData.uuid, participantData.uuid, true)
      html += @createColumn(participantData.createdAt, participantData.uuid, true)
      html += @createColumn(participantData.Apellido, participantData.uuid, true)
      html += @createColumn(participantData.Nombre, participantData.uuid, true)
      html += @createColumn(participantData.Sexo, participantData.uuid, false)
      birthday = participantData.Día + "/" + participantData.Mes + "/" + participantData.Año
      html += @createColumn(birthday, participantData.uuid, false)
      html += @createColumn(participantData.BarrioComunidad, participantData.uuid, false)
      html += @createColumn(participantData.Teléfono, participantData.uuid, false)
      html += @createColumn(participantData.Estecolateralparticipante, participantData.uuid, false)
      html += @createColumn(participantData.NombredeusuariodeFacebook, participantData.uuid, false)


      html += "</tr>"

    "</tbody></table></div>"

    html += "<button id='completeButton' name='completeButton' type='button'>Guardar</button>"


    @$el.html html

    $('#participants').dataTable({
      "bPaginate": false,
      "bSort": true,
      "bFilter": false
    });

    # make rows display in different colors
    #$('table tr').each (index, row) =>
    #  $(row).addClass("odd") if index % 2 is 1

    # @jQueryUIze(@$el)

#    $('#completeButton').click (e) ->
#      alert('in click')
#      @save

    $('#completeButton').click @save


    return

  createColumn: (value, participantId, searchField) ->
    if value is null or typeof value is "undefined"
      columnHtml = "<td></td>"
    else
      columnHtml = "<td>" + value + "</td>"
      if searchField is true
        @searchRows[participantId] += value
    return columnHtml

  jQueryUIze: ( $obj ) ->
    $obj.find('input[type=checkbox]').checkboxradio()
