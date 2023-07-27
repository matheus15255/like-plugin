jQuery(document).ready(function ($) {
  // Manipular votos via Ajax
  $('.like-button, .dislike-button').on('click', function () {
    var post_id = $(this).data('post_id');
    var action = $(this).data('action');

    $.ajax({
      type: 'POST',
      url: like_plugin_ajax.ajax_url,
      data: {
        action: 'like_dislike',
        post_id: post_id,
        action: action,
      },
      success: function (response) {
        location.reload(); // Recarrega a página para atualizar a contagem de likes e dislikes
      },
    });
  });
});
