@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __("messaging.group_title") }}</h2>
    <div class="card mb-4">
        <div class="card-header">{{ __("messaging.group.create_new") }}</div>
        <div class="card-body">
            <form id="createGroupForm">
                <div class="mb-3">
                    <label for="groupName" class="form-label">{{ __("messaging.group.group_name") }}</label>
                    <input type="text" class="form-control" id="groupName" name="groupName" placeholder="{{ __("messaging.group.group_name_placeholder") }}" required>
                </div>
                <div class="mb-3">
                    <label for="participants" class="form-label">{{ __("messaging.group.participants") }}</label>
                    <input type="text" class="form-control" id="participants" name="participants" required>
                </div>
                <button type="submit" class="btn btn-primary">{{ __("messaging.actions.create_group") }}</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">{{ __("messaging.group.your_groups") }}</div>
        <div class="card-body" id="groupsList">
            <!-- Groups will be loaded here -->
        </div>
    </div>
</div>

<script>
const translations = @json([
    'group_created' => __("messaging.group.group_created"),
    'error_creating' => __("messaging.group.error_creating"),
    'no_groups' => __("messaging.group.no_groups"),
    'group_id' => __("messaging.group.group_id"),
    'participants' => __("messaging.group.participants"),
    'delete_group' => __("messaging.actions.delete_group"),
    'delete_confirm' => __("messaging.group.delete_confirm"),
    'group_deleted' => __("messaging.group.group_deleted"),
    'error_deleting' => __("messaging.group.error_deleting")
]);

document.addEventListener('DOMContentLoaded', function() {
    loadGroups();

    document.getElementById('createGroupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const groupName = document.getElementById('groupName').value;
        const participants = document.getElementById('participants').value.split(',').map(p => p.trim());

        fetch('/api/whatsapp/groups', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ groupName, participants })
        })
        .then(res => res.json())
        .then(data => {
            alert(translations.group_created);
            loadGroups();
        })
        .catch(err => alert(translations.error_creating));
    });
});

function loadGroups() {
    fetch('/api/whatsapp/groups')
        .then(res => res.json())
        .then(groups => {
            const list = document.getElementById('groupsList');
            list.innerHTML = '';
            if (groups.length === 0) {
                list.innerHTML = '<p>' + translations.no_groups + '</p>';
                return;
            }
            groups.forEach(group => {
                const div = document.createElement('div');
                div.className = 'mb-3 p-3 border rounded';
                div.innerHTML = `
                    <strong>${group.name}</strong><br>
                    <small>${translations.group_id}: ${group.id}</small><br>
                    <span>${translations.participants}: ${group.participants.join(', ')}</span><br>
                    <button class="btn btn-danger btn-sm mt-2" onclick="deleteGroup('${group.id}')">${translations.delete_group}</button>
                `;
                list.appendChild(div);
            });
        });
}

function deleteGroup(groupId) {
    if (!confirm(translations.delete_confirm)) return;
    fetch(`/api/whatsapp/groups/${groupId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert(translations.group_deleted);
        loadGroups();
    })
    .catch(err => alert(translations.error_deleting));
}
</script>
@endsection