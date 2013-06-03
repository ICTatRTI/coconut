// Generated by CoffeeScript 1.6.2
var Coconut, Router, _ref,
  __hasProp = {}.hasOwnProperty,
  __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };

Router = (function(_super) {
  __extends(Router, _super);

  function Router() {
    _ref = Router.__super__.constructor.apply(this, arguments);
    return _ref;
  }

  Router.prototype.routes = {
    "login": "login",
    "logout": "logout",
    "design": "design",
    "select": "select",
    "show/customResults/:question_id": "showCustomResults",
    "show/results/:question_id": "showResults",
    "new/result/:question_id": "clientLookup",
    "new/result/:question_id/:client_id": "newResult",
    "edit/result/:result_id": "editResult",
    "delete/result/:result_id": "deleteResult",
    "delete/result/:result_id/:confirmed": "deleteResult",
    "edit/resultSummary/:question_id": "editResultSummary",
    "analyze/:form_id": "analyze",
    "delete/:question_id": "deleteQuestion",
    "edit/:question_id": "editQuestion",
    "manage": "manage",
    "sync": "sync",
    "sync/send": "syncSend",
    "sync/get": "syncGet",
    "configure": "configure",
    "map": "map",
    "reports": "reports",
    "reports/*options": "reports",
    "alerts": "alerts",
    "show/case/:caseID": "showCase",
    "users": "users",
    "messaging": "messaging",
    "help": "help",
    "summary/:client_id": "summary",
    "": "clientLookup"
  };

  Router.prototype.route = function(route, name, callback) {
    var _this = this;

    Backbone.history || (Backbone.history = new Backbone.History);
    if (!_.isRegExp(route)) {
      route = this._routeToRegExp(route);
    }
    return Backbone.history.route(route, function(fragment) {
      var args;

      args = _this._extractParameters(route, fragment);
      callback.apply(_this, args);
      $('#loading').slideDown();
      _this.trigger.apply(_this, ['route:' + name].concat(args));
      return $('#loading').fadeOut();
    }, this);
  };

  Router.prototype.help = function() {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.helpView) == null) {
          Coconut.helpView = new HelpView();
        }
        return Coconut.helpView.render();
      }
    });
  };

  Router.prototype.users = function() {
    return this.adminLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.usersView) == null) {
          Coconut.usersView = new UsersView();
        }
        return Coconut.usersView.render();
      }
    });
  };

  Router.prototype.messaging = function() {
    return this.adminLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.messagingView) == null) {
          Coconut.messagingView = new MessagingView();
        }
        return Coconut.messagingView.render();
      }
    });
  };

  Router.prototype.clientLookup = function() {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if (Coconut.config.local.get("mode") === "cloud") {
          return $("#content").html("            TODO: Cloud mode            Default view will show an overview of data          ");
        } else if (Coconut.config.local.get("mode") === "mobile") {
          if ((_ref1 = Coconut.scanBarcodeView) == null) {
            Coconut.scanBarcodeView = new ScanBarcodeView();
          }
          return Coconut.scanBarcodeView.render();
        }
      }
    });
  };

  Router.prototype.summary = function(clientID) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.clientSummary) == null) {
          Coconut.clientSummary = new ClientSummaryView();
        }
        Coconut.clientSummary.client = new Client({
          clientID: clientID
        });
        return Coconut.clientSummary.client.fetch({
          success: function() {
            return Coconut.clientSummary.render();
          },
          error: function() {
            return Coconut.router.navigate("/new/result/Client Demographics/" + clientID, true);
          }
        });
      }
    });
  };

  Router.prototype.userLoggedIn = function(callback) {
    return User.isAuthenticated({
      success: function(user) {
        return callback.success(user);
      },
      error: function() {
        Coconut.loginView.callback = callback;
        return Coconut.loginView.render();
      }
    });
  };

  Router.prototype.adminLoggedIn = function(callback) {
    return this.userLoggedIn({
      success: function(user) {
        if (user.isAdmin()) {
          return callback.success(user);
        }
      },
      error: function() {
        return $("#content").html("<h2>Must be an admin user</h2>");
      }
    });
  };

  Router.prototype.logout = function() {
    User.logout();
    return Coconut.router.navigate("", true);
  };

  Router.prototype.alerts = function() {
    return this.userLoggedIn({
      success: function() {
        if (Coconut.config.local.mode === "mobile") {
          return $("#content").html("Alerts not available in mobile mode.");
        } else {
          return $("#content").html("            <h1>Alerts</h1>            <ul>              <li>                <b>Localised Epidemic</b>: More than 10 cases per square kilometer in KATI district near BAMBI shehia (map <a href='#reports/location'>Map</a>). Recommend active case detection in shehia.              </li>              <li>                <b>Abnormal Data Detected</b>: Only 1 case reported in MAGHARIBI district for June 2012. Expected amount: 25. Recommend checking that malaria test kits are available at all health facilities in MAGHARIBI.              </li>            </ul>          ");
        }
      }
    });
  };

  Router.prototype.reports = function(options) {
    return this.userLoggedIn({
      success: function() {
        var reportViewOptions, _ref1;

        if (Coconut.config.local.mode === "mobile") {
          return $("#content").html("Reports not available in mobile mode.");
        } else {
          options = options != null ? options.split(/\//) : void 0;
          reportViewOptions = {};
          _.each(options, function(option, index) {
            if (!(index % 2)) {
              return reportViewOptions[option] = options[index + 1];
            }
          });
          if ((_ref1 = Coconut.reportView) == null) {
            Coconut.reportView = new ReportView();
          }
          return Coconut.reportView.render(reportViewOptions);
        }
      }
    });
  };

  Router.prototype.showCase = function(caseID) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.caseView) == null) {
          Coconut.caseView = new CaseView();
        }
        Coconut.caseView["case"] = new Case({
          caseID: caseID
        });
        return Coconut.caseView["case"].fetch({
          success: function() {
            return Coconut.caseView.render();
          }
        });
      }
    });
  };

  Router.prototype.configure = function() {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.localConfigView) == null) {
          Coconut.localConfigView = new LocalConfigView();
        }
        return Coconut.localConfigView.render();
      }
    });
  };

  Router.prototype.editResultSummary = function(question_id) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.resultSummaryEditor) == null) {
          Coconut.resultSummaryEditor = new ResultSummaryEditorView();
        }
        Coconut.resultSummaryEditor.question = new Question({
          id: unescape(question_id)
        });
        return Coconut.resultSummaryEditor.question.fetch({
          success: function() {
            return Coconut.resultSummaryEditor.render();
          }
        });
      }
    });
  };

  Router.prototype.editQuestion = function(question_id) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.designView) == null) {
          Coconut.designView = new DesignView();
        }
        Coconut.designView.render();
        return Coconut.designView.loadQuestion(unescape(question_id));
      }
    });
  };

  Router.prototype.deleteQuestion = function(question_id) {
    return this.userLoggedIn({
      success: function() {
        return Coconut.questions.get(unescape(question_id)).destroy({
          success: function() {
            Coconut.menuView.render();
            return Coconut.router.navigate("manage", true);
          }
        });
      }
    });
  };

  Router.prototype.sync = function(action) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.syncView) == null) {
          Coconut.syncView = new SyncView();
        }
        return Coconut.syncView.render();
      }
    });
  };

  Router.prototype.syncSend = function(action) {
    Coconut.router.navigate("", false);
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.syncView) == null) {
          Coconut.syncView = new SyncView();
        }
        return Coconut.syncView.sync.sendToCloud({
          success: function() {
            return Coconut.syncView.update();
          }
        });
      }
    });
  };

  Router.prototype.syncGet = function(action) {
    Coconut.router.navigate("", false);
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.syncView) == null) {
          Coconut.syncView = new SyncView();
        }
        return Coconut.syncView.sync.getFromCloud();
      }
    });
  };

  Router.prototype.manage = function() {
    return this.adminLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.manageView) == null) {
          Coconut.manageView = new ManageView();
        }
        return Coconut.manageView.render();
      }
    });
  };

  Router.prototype.newResult = function(question_id, client_id) {
    if (client_id == null) {
      throw "New results require a client id";
    }
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.questionView) == null) {
          Coconut.questionView = new QuestionView();
        }
        Coconut.questionView.result = new Result({
          question: unescape(question_id),
          ClientID: unescape(client_id)
        });
        Coconut.questionView.model = new Question({
          id: unescape(question_id)
        });
        return Coconut.questionView.model.fetch({
          success: function() {
            return Coconut.questionView.render();
          }
        });
      }
    });
  };

  Router.prototype.editResult = function(result_id) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.questionView) == null) {
          Coconut.questionView = new QuestionView();
        }
        Coconut.questionView.readonly = false;
        Coconut.questionView.result = new Result({
          _id: result_id
        });
        return Coconut.questionView.result.fetch({
          success: function() {
            Coconut.questionView.model = new Question({
              id: Coconut.questionView.result.question()
            });
            return Coconut.questionView.model.fetch({
              success: function() {
                return Coconut.questionView.render();
              }
            });
          }
        });
      }
    });
  };

  Router.prototype.deleteResult = function(result_id, confirmed) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.questionView) == null) {
          Coconut.questionView = new QuestionView();
        }
        Coconut.questionView.readonly = true;
        Coconut.questionView.result = new Result({
          _id: result_id
        });
        return Coconut.questionView.result.fetch({
          success: function() {
            if (confirmed === "confirmed") {
              return Coconut.questionView.result.destroy({
                success: function() {
                  Coconut.menuView.update();
                  return Coconut.router.navigate("show/results/" + (escape(Coconut.questionView.result.question())), true);
                }
              });
            } else {
              Coconut.questionView.model = new Question({
                id: Coconut.questionView.result.question()
              });
              return Coconut.questionView.model.fetch({
                success: function() {
                  Coconut.questionView.render();
                  $("#content").prepend("                    <h2>Are you sure you want to delete this result?</h2>                    <div id='confirm'>                      <a href='#delete/result/" + result_id + "/confirmed'>Yes</a>                      <a href='#show/results/" + (escape(Coconut.questionView.result.question())) + "'>Cancel</a>                    </div>                  ");
                  $("#confirm a").button();
                  $("#content form").css({
                    "background-color": "#333",
                    "margin": "50px",
                    "padding": "10px"
                  });
                  return $("#content form label").css({
                    "color": "white"
                  });
                }
              });
            }
          }
        });
      }
    });
  };

  Router.prototype.design = function() {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        $("#content").empty();
        if ((_ref1 = Coconut.designView) == null) {
          Coconut.designView = new DesignView();
        }
        return Coconut.designView.render();
      }
    });
  };

  Router.prototype.showCustomResults = function(question_id) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.customResultsView) == null) {
          Coconut.customResultsView = new CustomResultsView();
        }
        Coconut.customResultsView.question = new Question({
          id: unescape(question_id)
        });
        return Coconut.customResultsView.question.fetch({
          success: function() {
            return Coconut.customResultsView.render();
          }
        });
      }
    });
  };

  Router.prototype.showResults = function(question_id) {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.resultsView) == null) {
          Coconut.resultsView = new ResultsView();
        }
        Coconut.resultsView.question = new Question({
          id: unescape(question_id)
        });
        return Coconut.resultsView.question.fetch({
          success: function() {
            return Coconut.resultsView.render();
          }
        });
      }
    });
  };

  Router.prototype.map = function() {
    return this.userLoggedIn({
      success: function() {
        var _ref1;

        if ((_ref1 = Coconut.mapView) == null) {
          Coconut.mapView = new MapView();
        }
        return Coconut.mapView.render();
      }
    });
  };

  Router.prototype.startApp = function() {
    Coconut.config = new Config();
    return Coconut.config.fetch({
      success: function() {
        $("#footer-menu").html("          <center>          <span style='font-size:75%;display:inline-block'>            <span id='district'></span><br/>            <span id='user'></span>          </span>          <a href='#login'>Login</a>          <a href='#logout'>Logout</a>          <a id='reports' href='#reports'>Reports</a>          <a id='manage-button' style='display:none' href='#manage'>Manage</a>          &nbsp;          <a href='#sync/send'>Send data (last done: <span class='sync-sent-status'></span>)</a>          <a href='#sync/get'>Update (last done: <span class='sync-get-status'></span>)</a>          <a href='#help'>Help</a>          <span style='font-size:75%;display:inline-block'>Version<br/><span id='version'></span></span>          </center>        ");
        $("[data-role=footer]").navbar();
        $('#application-title').html(Coconut.config.title());
        Coconut.loginView = new LoginView();
        Coconut.questions = new QuestionCollection();
        Coconut.questionView = new QuestionView();
        Coconut.menuView = new MenuView();
        Coconut.syncView = new SyncView();
        Coconut.menuView.render();
        Coconut.syncView.update();
        return Backbone.history.start();
      },
      error: function() {
        var _ref1;

        if ((_ref1 = Coconut.localConfigView) == null) {
          Coconut.localConfigView = new LocalConfigView();
        }
        return Coconut.localConfigView.render();
      }
    });
  };

  return Router;

})(Backbone.Router);

Coconut = {};

Coconut.router = new Router();

Coconut.router.startApp();

Coconut.debug = function(string) {
  console.log(string);
  return $("#log").append(string + "<br/>");
};
