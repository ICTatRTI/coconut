class MenuView extends Backbone.View

  el: '.question-buttons'

  events:
    "change" : "render"


  render: =>

    @updateVersion()
    @checkReplicationStatus()

    return if "module" is Coconut.config.local.get("mode")


    @$el.html "
      <div id='navbar' data-role='navbar'>
        <ul></ul>
      </div>
    "

    Coconut.questions.fetch
      success: =>

        @$el.find("ul").html "
          <li>
            <a id='menu-retrieve-client' href='#new/result'>
              <h2>Find/Create Client<div id='menu-partial-amount'>&nbsp;</div></h2>
            </a>
          </li> "
        @$el.find("ul").append(Coconut.questions.map (question,index) ->
          "<li><a id='menu-#{index}' class='menu-#{index}' href='#show/results/#{escape(question.id)}'><h2>#{question.id}<div id='menu-partial-amount'></div></h2></a></li>"
        .join(" "))
        $(".question-buttons").navbar()
        # disable form buttons
        Coconut.questions.each (question,index) -> $(".menu-#{index}").addClass('ui-disabled')
        @update()


  updateVersion: ->
    $.ajax "version",
      success: (result) ->
        $("#version").html result
      error:
        $("#version").html "-"

  update: ->


    Coconut.questions.each (question,index) =>
      results = new ResultCollection()
      results.fetch
        include_docs: false
        question: question.id
        isComplete: false
        success: =>
          $("#menu-#{index} #menu-partial-amount").html results.length

    @updateVersion()

  checkReplicationStatus: =>
    return
    $.couch.login
      name: Coconut.config.get "local_couchdb_admin_username"
      password: Coconut.config.get "local_couchdb_admin_password"
      error: => console.log "Could not login"
      complete: =>
        $.ajax
          url: "/_active_tasks"
          dataType: 'json'
          success: (response) =>
            # This doesn't seem to work on Kindle - always get []. Works fine if I hit kindle from chrome on laptop. Go fig.
            progress = response?[0]?.progress
            if progress
              $("#databaseStatus").html "#{progress}% Complete"
              _.delay @checkReplicationStatus,1000
            else
              console.log "No database status update"
              $("#databaseStatus").html ""
              _.delay @checkReplicationStatus,60000
          error: (error) =>
            console.log "Could not check active_tasks: #{JSON.stringify(error)}"
            _.delay @checkReplicationStatus,60000
