{{-- Dialog konfirmasi hapus aset (DELETE + CSRF). Isi #delete-id & [data-delete-name] sebelum membuka. --}}
<div class="modal fade rp-modal" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="delete-title">
    <div class="modal-dialog modal-sm" role="document">
        <form method="POST" action="{{ route('item_master.destroy') }}" class="modal-content">
            @csrf
            @method('DELETE')
            <input type="hidden" name="id" id="delete-id" value="{{ $deleteId ?? '' }}">
            <div class="modal-body text-center">
                <span class="rp-modal-icon"><i class="bi bi-trash3"></i></span>
                <h4 id="delete-title">Delete this item?</h4>
                <p><strong data-delete-name>{{ $deleteName ?? '' }}</strong> will be removed from the inventory list.
                    Its history stays in the database.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash3"></i> Delete</button>
            </div>
        </form>
    </div>
</div>
