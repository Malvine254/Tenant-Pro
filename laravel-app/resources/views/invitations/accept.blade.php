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
            Create your password to activate your TenantPro account.

        </div>


        <div style="margin-bottom:14px;color:#e2e8f0;font-size:13px;">

            Email:
            <strong>
                {{ $invitation->email }}
            </strong>

        </div>


        <form method="POST" action="{{ url()->current() }}">

            @csrf


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