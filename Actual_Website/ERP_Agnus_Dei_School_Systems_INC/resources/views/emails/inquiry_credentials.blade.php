<x-mail::message>
# Welcome to Agnus Dei School Systems!

Hello **{{ $firstName }}**,

Thank you for your interest in Agnus Dei. To proceed with the admission process, we have generated an official institutional email for you.

Please use the following credentials to access the Agnus Dei Portal:

**Email Address:**  
`{{ $email }}`

**Temporary Password:**  
`{{ $password }}`

<x-mail::button :url="url('/login')">
Access Portal
</x-mail::button>

For your security, we strongly advise changing this password upon first login.

Warm regards,<br>
**Agnus Dei Admissions Office**
</x-mail::message>
