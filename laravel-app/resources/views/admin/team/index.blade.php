@extends('admin.layout')
@section('page-title', 'Team Access')

@section('content')
<style>
    .team-grid{display:grid;grid-template-columns:minmax(300px,.8fr) minmax(0,1.5fr);gap:18px;align-items:start}.team-card{background:linear-gradient(180deg,#111827,#0b1220);border:1px solid rgba(148,163,184,.2);border-radius:18px;padding:20px;box-shadow:0 18px 36px rgba(2,6,23,.28)}.team-card h3{margin:0 0 6px;color:#f8fafc}.team-card>p{color:#94a3b8;line-height:1.55;margin:0 0 18px}.team-form{display:grid;gap:14px}.team-form .split{display:grid;grid-template-columns:1fr 1fr;gap:12px}.team-form label{display:block;color:#cbd5e1;font-size:12px;font-weight:700;margin-bottom:6px}.team-form input{width:100%;background:rgba(15,23,42,.7);border:1px solid rgba(148,163,184,.28);color:#f8fafc;border-radius:10px;padding:11px 12px}.team-member{display:grid;grid-template-columns:minmax(180px,1fr) minmax(260px,1.25fr);gap:16px;padding:16px 0;border-bottom:1px solid rgba(148,163,184,.14)}.team-member:last-child{border-bottom:0}.team-person strong{display:block;color:#f8fafc}.team-person span{display:block;color:#94a3b8;font-size:12px;margin-top:4px}.team-actions{display:flex;gap:8px;align-items:end;flex-wrap:wrap}.team-actions form:first-child{display:grid;grid-template-columns:1fr 1fr auto auto;gap:8px;align-items:end;flex:1}.team-actions input{min-width:0;background:rgba(15,23,42,.7);border:1px solid rgba(148,163,184,.24);color:#f8fafc;border-radius:9px;padding:9px}.team-state{display:inline-flex;margin-top:9px;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:800}.team-state.on{background:rgba(34,197,94,.12);color:#86efac}.team-state.off{background:rgba(248,113,113,.12);color:#fca5a5}.team-note{padding:12px;border-radius:12px;background:rgba(37,99,235,.1);border:1px solid rgba(96,165,250,.18);color:#bfdbfe;font-size:12px;line-height:1.55}@media(max-width:980px){.team-grid{grid-template-columns:1fr}.team-member{grid-template-columns:1fr}.team-actions form:first-child{grid-template-columns:1fr 1fr}}@media(max-width:620px){.team-form .split,.team-actions form:first-child{grid-template-columns:1fr}}
</style>

<div class="admin-page-header">
    <div><h2>Team access</h2><p>Give trusted staff their own secure login to manage {{ $owner->name }} rental operations.</p></div>
</div>

<div class="team-grid">
    <section class="team-card">
        <h3>Add team member</h3>
        <p>The new member receives a secure password-setup email. Never share the landlord owner's password.</p>
        <form method="POST" action="{{ route('admin.team.store') }}" class="team-form">
            @csrf
            <div class="split">
                <div><label for="team_first_name">First name</label><input id="team_first_name" name="first_name" value="{{ old('first_name') }}" required></div>
                <div><label for="team_last_name">Last name</label><input id="team_last_name" name="last_name" value="{{ old('last_name') }}"></div>
            </div>
            <div><label for="team_email">Email address</label><input id="team_email" type="email" name="email" value="{{ old('email') }}" required></div>
            <div><label for="team_phone">Phone number, optional</label><input id="team_phone" name="phone_number" value="{{ old('phone_number') }}"></div>
            <div><label for="team_password">Confirm your password</label><input id="team_password" type="password" name="current_password" autocomplete="current-password" required></div>
            <div class="team-note">Team members can manage properties, units, tenants, invitations, invoices, payments, and chats. Owner billing, Daraja settings, and team access remain restricted.</div>
            <button class="btn btn-primary" type="submit">Add member and send setup link</button>
        </form>
    </section>

    <section class="team-card">
        <h3>Current team</h3>
        <p>{{ $members->total() }} delegated {{ Str::plural('account', $members->total()) }}. Suspended members cannot sign in and their API tokens are revoked.</p>
        @forelse($members as $member)
            <div class="team-member">
                <div class="team-person">
                    <strong>{{ $member->name }}</strong>
                    <span>{{ $member->email }}</span>
                    <span>Invited {{ $member->team_invited_at?->format('d M Y') ?? '—' }}</span>
                    <span class="team-state {{ $member->is_active ? 'on' : 'off' }}">{{ $member->is_active ? 'Active access' : 'Suspended' }}</span>
                </div>
                <div class="team-actions">
                    <form method="POST" action="{{ route('admin.team.update', $member) }}">
                        @csrf @method('PUT')
                        <input name="first_name" value="{{ $member->first_name ?: Str::before($member->name, ' ') }}" aria-label="First name" required>
                        <input name="last_name" value="{{ $member->last_name }}" aria-label="Last name">
                        <input name="phone_number" value="{{ $member->phone_number }}" aria-label="Phone number" placeholder="Phone">
                        <input type="hidden" name="is_active" value="0">
                        <label style="display:flex;align-items:center;gap:6px;margin:0;padding:9px;color:#cbd5e1;"><input type="checkbox" name="is_active" value="1" style="width:auto" {{ $member->is_active ? 'checked' : '' }}> Active</label>
                        <button class="btn btn-secondary" type="submit">Save</button>
                    </form>
                    <form method="POST" action="{{ route('admin.team.setup-link', $member) }}">
                        @csrf
                        <button class="btn btn-secondary" type="submit">Send setup link</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">No team members yet.</div>
        @endforelse
        <div class="pagination">{{ $members->links() }}</div>
    </section>
</div>
@endsection
