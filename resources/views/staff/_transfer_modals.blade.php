{{-- Modal Transfer satu aset & Transfer All. $options dari StaffController::read() --}}
@php
    $staffSelect = function (string $name, string $id, string $autofill) use ($options) {
        $html = '<select id="'.$id.'" name="'.$name.'" class="form-control rf-modal-select" data-placeholder="Search staff" data-autofill="'.$autofill.'" required><option value=""></option>';
        foreach ($options['staff'] as $group => $people) {
            $html .= '<optgroup label="'.e($group).'">';
            foreach ($people as $p) {
                $html .= '<option value="'.$p->id.'" data-department="'.$p->department_id.'" data-location="'.$p->location_id.'">'.e($p->name).'</option>';
            }
            $html .= '</optgroup>';
        }

        return $html.'</select>';
    };
    $simpleSelect = function (string $name, string $id, $items, string $placeholder) {
        $html = '<select id="'.$id.'" name="'.$name.'" class="form-control rf-modal-select" data-placeholder="'.e($placeholder).'" required><option value=""></option>';
        foreach ($items as $value => $label) {
            $html .= '<option value="'.e($value).'">'.e($label).'</option>';
        }

        return $html.'</select>';
    };
@endphp

{{-- ===== Transfer satu aset ===== --}}
<div class="modal fade rp-modal rf-form-modal" id="transfer-modal" tabindex="-1" role="dialog" aria-labelledby="transfer-title" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <form class="modal-content rf-modal-form" action="{{ route('staff.item_mutation') }}" novalidate>
            <input type="hidden" name="inventory_number">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                <h4 class="modal-title" id="transfer-title"><i class="bi bi-arrow-left-right"></i> Transfer Item</h4>
                <p>Move this item to another person. A new history entry is recorded.</p>
            </div>
            <div class="modal-body rf-section">
                <div class="alert alert-danger rf-modal-alert" style="display:none"></div>
                <div class="sf-transfer-item">
                    <span class="rf-code" data-item-number></span>
                    <b data-item-name></b>
                    <p data-item-desc></p>
                </div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        <label for="t-transaction_at">Date &amp; time <span class="rp-req">*</span></label>
                        <input type="datetime-local" id="t-transaction_at" name="transaction_at" class="form-control" required>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="t-condition_id">Condition <span class="rp-req">*</span></label>
                        {!! $simpleSelect('condition_id', 't-condition_id', $options['conditions'], 'Select condition') !!}
                    </div>
                </div>
                <div class="form-group">
                    <label for="t-staff_id">New responsible person <span class="rp-req">*</span></label>
                    {!! $staffSelect('staff_id', 't-staff_id', '') !!}
                </div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        <label for="t-department_id">Department <span class="rp-req">*</span></label>
                        {!! $simpleSelect('department_id', 't-department_id', $options['departments'], 'Select department') !!}
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="t-location_id">Location <span class="rp-req">*</span></label>
                        {!! $simpleSelect('location_id', 't-location_id', $options['locations'], 'Select location') !!}
                    </div>
                </div>
                <div class="form-group">
                    <label for="t-purpose">Purpose</label>
                    <textarea id="t-purpose" name="purpose" class="form-control" rows="2" placeholder="Purpose"></textarea>
                </div>
                <div class="form-group">
                    <label for="t-remark">Remark</label>
                    <textarea id="t-remark" name="remark" class="form-control" rows="2" placeholder="Remark"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-left-right"></i> Transfer</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Transfer All ===== --}}
<div class="modal fade rp-modal rf-form-modal" id="transfer-all-modal" tabindex="-1" role="dialog" aria-labelledby="transfer-all-title" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <form class="modal-content rf-modal-form" action="{{ route('staff.transfer_all') }}" novalidate>
            <input type="hidden" name="id" value="{{ $staff->id }}">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                <h4 class="modal-title" id="transfer-all-title"><i class="bi bi-arrow-left-right"></i> Transfer All</h4>
                <p>Move all <b>{{ $total }}</b> {{ \Illuminate\Support\Str::plural('item', $total) }} held by <b>{{ $staff->name }}</b> to another person. Each item keeps its current condition.</p>
            </div>
            <div class="modal-body rf-section">
                <div class="alert alert-danger rf-modal-alert" style="display:none"></div>
                <div class="form-group">
                    <label for="ta-staff_id">New responsible person <span class="rp-req">*</span></label>
                    {!! $staffSelect('staff_id_transfer', 'ta-staff_id', '_transfer') !!}
                </div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        <label for="ta-department_id">Department <span class="rp-req">*</span></label>
                        {!! $simpleSelect('department_id_transfer', 'ta-department_id', $options['departments'], 'Select department') !!}
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="ta-location_id">Location <span class="rp-req">*</span></label>
                        {!! $simpleSelect('location_id_transfer', 'ta-location_id', $options['locations'], 'Select location') !!}
                    </div>
                </div>
                <div class="form-group">
                    <label for="ta-purpose">Purpose</label>
                    <textarea id="ta-purpose" name="purpose" class="form-control" rows="2" placeholder="Purpose"></textarea>
                </div>
                <div class="form-group">
                    <label for="ta-remark">Remark</label>
                    <textarea id="ta-remark" name="remark" class="form-control" rows="2" placeholder="Remark"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn sf-btn-transfer"><i class="bi bi-arrow-left-right"></i> Transfer {{ $total }} {{ \Illuminate\Support\Str::plural('item', $total) }}</button>
            </div>
        </form>
    </div>
</div>
