@extends('layouts.app')

@section('content')
<div class="container">
    <h2>WhatsApp Group Management</h2>
    <div class="card mb-4">
        <div class="card-header">Create New Group</div>
        <div class="card-body">
            <form id="createGroupForm">
                <div class="mb-3">
                    <label for="groupName" class="form-label">Group Name</label>
                    <input type="text" class="form-control" id="groupName" name="groupName" required>
                </div>
                <div class="mb-3">
                    <label for="participants" class="form-label">Participants (comma separated phone numbers)</label>
                    <input type="text" class="form-control" id="participants" name="participants" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Group</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Your Groups</div>
        <div class="card-body" id="groupsList">
            <!-- Groups will be loaded here -->
        </div>
    </div>
</div>

<script>
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
            alert('Group created successfully!');
            loadGroups();
        })
        .catch(err => alert('Error creating group'));
    });
});

function loadGroups() {
    fetch('/api/whatsapp/groups')
        .then(res => res.json())
        .then(groups => {
            const list = document.getElementById('groupsList');
            list.innerHTML = '';
            if (groups.length === 0) {
                list.innerHTML = '<p>No groups found.</p>';
                return;
            }
            groups.forEach(group => {
                const div = document.createElement('div');
                div.className = 'mb-3 p-3 border rounded';
                div.innerHTML = `
                    <strong>${group.name}</strong><br>
                    <small>ID: ${group.id}</small><br>
                    <span>Participants: ${group.participants.join(', ')}</span><br>
                    <button class="btn btn-danger btn-sm mt-2" onclick="deleteGroup('${group.id}')">Delete Group</button>
                `;
                list.appendChild(div);
            });
        });
}

function deleteGroup(groupId) {
    if (!confirm('Are you sure you want to delete this group?')) return;
    fetch(`/api/whatsapp/groups/${groupId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert('Group deleted!');
        loadGroups();
    })
    .catch(err => alert('Error deleting group'));
}
</script>
@endsection