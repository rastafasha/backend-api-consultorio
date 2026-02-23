@component('mail::message')
# Reset Password
Reset or change your password.
@component('mail::button', ['url' => 'https://pconsultorio.health-connect.me/#/change-password?token='.$token])
Change Password
@endcomponent
Thanks,<br>
{{ config('app.name') }}
@endcomponent