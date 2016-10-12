jQuery(document).ready(function($) {
  $('[id^="toggle-discount"]').submit(function() {
    $user_id = $(this).data('id');
    $user_discount = $(this).data('discount');
    var data = {
      action: 'toggle_discount',
      user_id: $user_id,
      user_discount: $user_discount
    };

    $.post( ajaxurl, data, function (results) {
      var new_labels = $.parseJSON(results);
      $('#toggle-discount-' + $user_id).children('input').val(new_labels.button);
      $('#toggle-discount-' + $user_id).children('label').text(new_labels.label);
    })

    return false;
  });
});

jQuery(document).ready(function($) {
  $('[id^="buy-product"]').submit(function() {
    var data = {
      action: 'buy_product'
    };

    $.post( my_ajax_object.ajaxurl, data, function (results) {
      alert(results);
    })

    return false;
  });
});