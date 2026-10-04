@extends('layouts.app')
@section('title', 'System Users & Roles')
@section('content')
<div class="flex justify-between mb-6">
    <h3 class="text-lg font-semibold">User Management</h3>
    <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium"><i class="fas fa-user-plus mr-1"></i> Add User</button>
</div>

<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Role</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Last Login</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($users as $user)
        <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 text-sm font-semibold">{{ $user->name }}</td>
            <td class="px-5 py-3 text-sm text-gray-500">{{ $user->email }}</td>
            <td class="px-5 py-3 text-sm">
                @if($user->role === 'superadmin') <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded text-xs font-medium">Super Admin</span>
                @elseif($user->role === 'admin') <span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded text-xs font-medium">Administrator</span>
                @elseif($user->role === 'hr') <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-medium">HR Manager</span>
                @else <span class="px-2 py-0.5 bg-gray-100 text-gray-800 rounded text-xs font-medium">Manager</span> @endif
            </td>
            <td class="px-5 py-3 text-sm">
                @if($user->is_active) <span class="text-green-600"><i class="fas fa-circle text-[8px] mr-1"></i> Active</span>
                @else <span class="text-red-500"><i class="fas fa-circle text-[8px] mr-1"></i> Disabled</span> @endif
            </td>
            <td class="px-5 py-3 text-sm text-gray-400 font-mono">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</td>
            <td class="px-5 py-3 text-right">
                @if($user->role !== 'superadmin' && $user->email !== 'superadmin@profitix.com')
                <button onclick="editUser({{ $user->toJson() }})" class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs mr-1"><i class="fas fa-edit"></i></button>
                @if(auth()->id() !== $user->id)
                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete user?')">@csrf @method('DELETE')<button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs"><i class="fas fa-trash"></i></button></form>
                @endif
                @else
                <span class="text-xs text-gray-400 italic">Protected</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No users found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Add User Modal --}}
<div id="addUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-semibold mb-4">Add User</h3>
        <form method="POST" action="{{ route('users.store') }}">@csrf
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Name *</label><input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Email *</label><input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Password *</label><input type="password" name="password" required minlength="6" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-4"><label class="block text-sm font-medium mb-1">Role *</label>
                <select name="role" required class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="manager">Manager</option>
                    <option value="hr">HR Manager</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Create User</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit User Modal --}}
<div id="editUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-semibold mb-4">Edit User</h3>
        <form method="POST" id="editUserForm">@csrf @method('PUT')
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Name *</label><input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Email *</label><input type="email" name="email" id="edit_email" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Password (Leave blank to keep current)</label><input type="password" name="password" minlength="6" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Role *</label>
                <select name="role" id="edit_role" required class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="manager">Manager</option>
                    <option value="hr">HR Manager</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="mb-4 flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="edit_active" class="rounded border-gray-300 text-blue-600">
                <label for="edit_active" class="text-sm font-medium">Account is Active</label>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('editUserForm').action = '{{ url("/users") }}/' + user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_active').checked = user.is_active;
    document.getElementById('editUserModal').classList.remove('hidden');
}
</script>
@endsection
