function Talk($el) {
    this.$el = $el;
    this.id = $el.data('id');
    this.status = $el.data('status');
    this.baseUrl = '/admin/talks/';
};

Talk.prototype.favorite = function() {
    var _this = this;
    var url = this.baseUrl + this.id + '/favorite';
    var shouldClear = this.$el.find('i').hasClass('star-favorite--selected');
    var data = {
        id: this.id,
        status: shouldClear ? 0 : this.status
    };

    $.ajax({
        type: "POST",
        url: url,
        data: data,
        success: function (data) {
            $('.star-favorite' + _this.id).removeClass('star-favorite--selected');
            if (!shouldClear) {
                _this.$el.find('i').addClass('star-favorite--selected');
            }
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
    var talk = new Talk($(this));
    e.preventDefault();
    talk.favorite();
});

$('.js-talk-select').on('click', function(e) {
    var talk = new Talk($(this));
    e.preventDefault();
    talk.select();
});