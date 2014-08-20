function Talk(id, $el) {
  this.$el = $el;
  this.id = id;
  this.baseUrl = '/admin/talks/';
};

Talk.prototype.favorite = function() {
  var _this = this;
  var url = this.baseUrl + this.id + '/favorite';
  var data = { id: this.id };

  if (this.$el.find('i').hasClass('star-favorite--selected')) {
    data.delete = true;
  }

  $.ajax({
    type: "POST",
    url: url,
    data: data,
    success: function(data) {
        _this.$el.find('i').toggleClass('star-favorite--selected');
    },
    error: _this.onError
  });
};

Talk.prototype.select = function() {
  var _this = this;
  var url = this.baseUrl + this.id + '/select';
  var data = { id: this.id };

  if (this.$el.find('i').hasClass('check-select--selected')) {
    data.delete = true;
  }

  $.ajax({
    type: "POST",
    url: url,
    data: data,
    success: function() {
      _this.$el.find('i').toggleClass('check-select--selected');
    },
    error: _this.onError
  });
};

Talk.prototype.onError = function(xhr, status, errorMessage) {
    var response = $.parseJSON(xhr.responseText);
    console.log(status + ': ' + errorMessage);
    if (response.message) {
        alert(response.message);
    }
};

// Add Listeners
$('.js-talk-favorite').on('click', function(e) {
    var talk = new Talk($(this).data('id'), $(this));
    e.preventDefault();
    talk.favorite();
});

$('.js-talk-select').on('click', function(e) {
    var talk = new Talk($(this).data('id'), $(this));
    e.preventDefault();
    talk.select();
});