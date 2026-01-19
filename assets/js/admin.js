jQuery(function ($) {
  $(document).on('click', '.mpe-orcid-import', function () {
    var $btn = $(this);
    var postId = $btn.data('post-id');
    var $status = $btn.closest('.mpe-orcid-box').find('.mpe-orcid-status');

    $status.removeClass('mpe-ok mpe-err').text('Importando desde ORCID...');
    $btn.prop('disabled', true);

    $.post(MPE_IO.ajax_url, {
      action: 'mpe_io_orcid_import',
      nonce: MPE_IO.nonce,
      post_id: postId
    })
      .done(function (resp) {
        if (!resp || !resp.success) {
          var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Error desconocido';
          $status.addClass('mpe-err').text('Error: ' + msg);
          return;
        }

        var s = resp.data.stats || {};
        $status.addClass('mpe-ok').html(
          '<strong>OK.</strong> ' +
          'Nuevas: ' + (s.created ?? 0) +
          ' · Actualizadas: ' + (s.updated ?? 0) +
          ' · Sin cambios: ' + (s.skipped ?? 0) +
          '<br><small>Última sync: ' + (resp.data.last_sync || '') + '</small>'
        );
      })
      .fail(function (xhr) {
        var msg = 'Error de red.';
        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
          msg = xhr.responseJSON.data.message;
        }
        $status.addClass('mpe-err').text('Error: ' + msg);
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });
});
