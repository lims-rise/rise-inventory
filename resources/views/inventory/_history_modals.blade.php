{{-- Modal History, Maintenance, dan konfirmasi hapus. $options dari ItemMasterController::historyOptions() --}}

{{-- ===== History ===== --}}
<div class="modal fade rp-modal rf-form-modal" id="history-modal" tabindex="-1" role="dialog" aria-labelledby="history-title" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <form class="modal-content rf-modal-form" action="{{ route('item_transaction.save') }}" novalidate>
            <input type="hidden" name="id">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                <h4 class="modal-title" id="history-title"><i class="bi bi-clock-history"></i> <span class="rf-modal-mode">New</span> History</h4>
                <p>Who is responsible for this item, where it is, and its condition.</p>
            </div>
            <div class="modal-body rf-section">
                <div class="alert alert-danger rf-modal-alert" style="display:none"></div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        <label for="h-transaction_at">Date <span class="rp-req">*</span></label>
                        <input type="date" id="h-transaction_at" name="transaction_at" class="form-control" required>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="h-condition_id">Condition <span class="rp-req">*</span></label>
                        <select id="h-condition_id" name="condition_id" class="form-control rf-modal-select" data-placeholder="Select condition" required>
                            <option value=""></option>
                            @foreach ($options['conditions'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="h-staff_id">Responsible Person <span class="rp-req">*</span></label>
                    <select id="h-staff_id" name="staff_id" class="form-control rf-modal-select" data-placeholder="Search staff" required>
                        <option value=""></option>
                        @foreach ($options['staff'] as $group => $people)
                            <optgroup label="{{ $group }}">
                                @foreach ($people as $p)
                                    <option value="{{ $p->id }}" data-department="{{ $p->department_id }}" data-location="{{ $p->location_id }}">{{ $p->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="rp-help"><i class="bi bi-magic"></i> Department and location are filled in from the staff profile. You can still change them.</p>
                </div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        <label for="h-department_id">Department <span class="rp-req">*</span></label>
                        <select id="h-department_id" name="department_id" class="form-control rf-modal-select" data-placeholder="Select department" required>
                            <option value=""></option>
                            @foreach ($options['departments'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="h-location_id">Location <span class="rp-req">*</span></label>
                        <select id="h-location_id" name="location_id" class="form-control rf-modal-select" data-placeholder="Select location" required>
                            <option value=""></option>
                            @foreach ($options['locations'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="h-purpose">Purpose</label>
                    <textarea id="h-purpose" name="purpose" class="form-control" rows="2" placeholder="e.g. NHMRC Pilot sampling"></textarea>
                </div>
                <div class="form-group">
                    <label for="h-remark">Remark</label>
                    <textarea id="h-remark" name="remark" class="form-control" rows="2" placeholder="Remark"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Save</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Maintenance ===== --}}
<div class="modal fade rp-modal rf-form-modal" id="maintenance-modal" tabindex="-1" role="dialog" aria-labelledby="maintenance-title" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <form class="modal-content rf-modal-form" action="{{ route('item_transaction.save_maintenance') }}" novalidate>
            <input type="hidden" name="id_maintenance">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                <h4 class="modal-title" id="maintenance-title"><i class="bi bi-tools"></i> <span class="rf-modal-mode">New</span> Maintenance</h4>
                <p>Service details and how often this item should be checked.</p>
            </div>
            <div class="modal-body rf-section">
                <div class="alert alert-danger rf-modal-alert" style="display:none"></div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        <label for="m-last_service_date">Last service date <span class="rp-req">*</span></label>
                        <input type="date" id="m-last_service_date" name="last_service_date" class="form-control" required>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="m-id_services">Service type</label>
                        <select id="m-id_services" name="id_services" class="form-control rf-modal-select" data-placeholder="Select service type">
                            <option value=""></option>
                            @foreach ($options['services'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        <label for="m-provider_contact">Service provider contact</label>
                        <input type="text" id="m-provider_contact" name="provider_contact" class="form-control" maxlength="100" placeholder="Name, phone or email">
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="m-staff_id">PIC</label>
                        <select id="m-staff_id" name="staff_id" class="form-control rf-modal-select" data-placeholder="Search staff">
                            <option value=""></option>
                            @foreach ($options['staff'] as $group => $people)
                                <optgroup label="{{ $group }}">
                                    @foreach ($people as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="m-service_desc">Service description</label>
                    <textarea id="m-service_desc" name="service_desc" class="form-control" rows="2" maxlength="255" placeholder="What was done"></textarea>
                </div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        <label for="m-id_frequency">Frequency</label>
                        <select id="m-id_frequency" name="id_frequency" class="form-control rf-modal-select" data-placeholder="Select frequency">
                            <option value=""></option>
                            @foreach ($options['frequencies'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="m-service_schedule">Schedule prior (weeks)</label>
                        <input type="number" id="m-service_schedule" name="service_schedule" class="form-control" min="0" max="520" step="1" placeholder="e.g. 2">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Save</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Konfirmasi hapus ===== --}}
<div class="modal fade rp-modal" id="remove-modal" tabindex="-1" role="dialog" aria-labelledby="remove-title">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <span class="rp-modal-icon"><i class="bi bi-trash3"></i></span>
                <h4 id="remove-title">Delete this record?</h4>
                <p><strong data-remove-label></strong> will be removed from this item.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="remove-confirm"><i class="bi bi-trash3"></i> Delete</button>
            </div>
        </div>
    </div>
</div>
