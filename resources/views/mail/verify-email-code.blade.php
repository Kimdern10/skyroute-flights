<x-mail::message>
# Confirm your email

Hi {{ str($name)->before(' ') }}, welcome to {{ $business }}! Enter this code on the sign-up page to confirm your email address:

<div class="code">{{ $code }}</div>

The code works for **{{ $minutes }} minutes**. Never share it with anyone, our team will never ask for it.

If you didn't create a {{ $business }} account, you can ignore this email.
</x-mail::message>
