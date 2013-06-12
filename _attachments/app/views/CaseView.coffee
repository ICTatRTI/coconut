class CaseView extends Backbone.View
  el: '#content'

  render: (scrollTargetID) =>
    @$el.html "
      <style>
        table.tablesorter {font-size: 125%}
      </style>

      <h1>Case ID: #{@case.MalariaCaseID()}</h1>
      <h3>Last Modified: #{@case.LastModifiedAt()}</h3>
      <h3>Questions: #{@case.Questions()}</h3>
      #{
        _.map( ("region,district,constituan,ward".split(",")), (locationType) =>
          "<h3>#{locationType.humanize()}: #{@case.location(locationType)}</h3>"
        ).join("")
      }
    "
#      <pre>
#      #{JSON.stringify(@case.toJSON(), null, 4)}
#      </pre>
#    "

    tables = ["USSD Notification"]
    Coconut.questions.fetch
      success: =>
        tables = tables.concat Coconut.questions.map (question) ->
          question.label()

        @$el.append _.map(tables, (tableType) =>
          if @case[tableType]?
            if tableType is "Household Members"
              _.map(@case[tableType], (householdMember) =>
                @createObjectTable(tableType,householdMember)
              ).join("")
            else
              @createObjectTable(tableType,@case[tableType])
        ).join("")
        _.each $('table tr'), (row, index) ->
          $(row).addClass("odd") if index%2 is 1
        $('html, body').animate({ scrollTop: $("##{scrollTargetID}").offset().top }, 'slow') if scrollTargetID?


  createObjectTable: (name,object) =>
    "
      <h2 id=#{object._id}>#{name}</h2>
      <table class='tablesorter'>
        <thead>
          <tr>
            <th>Field</th>
            <th>Value</th>
          </tr>
        </thead>
        <tbody>
          #{
            _.map(object, (value, field) ->
              return if "#{field}".match(/_id|_rev|collection/)
              #if (User.currentUser.hasRole("reports") or User.currentUser is null) and not User.currentUser.hasRole("admin") and _.contains(Coconut.identifyingAttributes, field)
              #  value = b64_sha1(value) if _.contains(Coconut.identifyingAttributes, field)
              "
                <tr>
                  <td>#{field}</td><td>#{value}</td>
                </tr>
              "
            ).join("")
          
          }
        </tbody>
      </table>
    "