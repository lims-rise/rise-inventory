{{-- Dialog hapus bersama untuk halaman Config. Tombol pemicu: [data-delete="url"][data-name="…"]. $what, $note --}}
<div class="modal fade rp-modal" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="delete-title">
    <div class="modal-dialog modal-sm" role="document">
        <form method="POST" class="modal-content" id="delete-form">
            @csrf
            @method('DELETE')
            <div class="modal-body text-center">
                <span class="rp-modal-icon"><i class="bi bi-trash3"></i></span>
                <h4 id="delete-title">Delete this {{ $what }}?</h4>
                <p><strong data-delete-name></strong> will be permanently deleted. {{ $note ?? '' }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash3"></i> Delete</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
$(document).on('click', '[data-delete]', function () {
    $('#delete-form').attr('action', $(this).data('delete'));
    $('[data-delete-name]').text($(this).data('name'));
    $('#delete-modal').modal('show');
});
</script>
@endpush
