// Generated by CoffeeScript 1.6.3
var MessageCollection, _ref,
  __hasProp = {}.hasOwnProperty,
  __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };

MessageCollection = (function(_super) {
  __extends(MessageCollection, _super);

  function MessageCollection() {
    _ref = MessageCollection.__super__.constructor.apply(this, arguments);
    return _ref;
  }

  MessageCollection.prototype.model = Message;

  MessageCollection.prototype.url = "/message";

  return MessageCollection;

})(Backbone.Collection);
