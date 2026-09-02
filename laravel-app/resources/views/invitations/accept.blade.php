@extends('admin.layout')

@section('page-title', 'Accept Invitation')

@section('content')

<div style="max-width:380px;margin:60px auto;">

    <div class="card">

        <h2 style="margin-bottom:20px;font-size:18px;">
            Accept Invitation
        </h2>


        <div style="margin-bottom:14px;padding:10px 12px;border:1px solid rgba(255,255,255,.35);background:rgba(96,165,250,.12);border-radius:10px;font-size:12px;color:#dbeafe;line-height:1.5;">

            Welcome {{ $invitation->invitee_name }}.
            Create your password to activate your Starmax Tenant Services account.

        </div>


        <div style="margin-bottom:14px;color:#e2e8f0;font-size:13px;">

            Email:
            <strong>
                {{ $invitation->email }}
            </strong>

        </div>


        <form method="POST" action="{{ url()->current() }}">

            @csrf

            @if($invitation->invite_type === 'LANDLORD')
                <div style="margin-bottom:14px;padding:10px 12px;border:1px solid rgba(96,165,250,.35);background:rgba(96,165,250,.12);border-radius:10px;font-size:12px;color:#dbeafe;line-height:1.5;">
                    Complete your profile before activating your landlord workspace. You can add properties and configure recurring bills after sign-in.
                </div>

                <div class="form-group">
                    <label>First name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Last name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Phone number</label>
                    <input type="tel" name="phone_number" value="{{ old('phone_number') }}" placeholder="e.g. +254712345678" required>
                    @error('phone_number')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            @endif


            <div class="form-group">

                <label>Password</label>

                <input 
                    type="password"
                    name="password"
                    required
                >

                @error('password')
                    <div class="form-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <div class="form-group">

                <label>Confirm Password</label>

                <input 
                    type="password"
                    name="password_confirmation"
                    required
                >

            </div>


            <button 
                type="submit" 
                class="btn btn-primary"
                style="width:100%;padding:10px;"
            >
                Activate Account
            </button>


        </form>

    </div>

</div>

@endsection
