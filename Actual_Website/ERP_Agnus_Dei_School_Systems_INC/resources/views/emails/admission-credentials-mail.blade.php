Welcome to Agnus Dei School! Your admission has been approved.

Student: {{ $student->first_name }} {{ $student->last_name }}
Student Number: {{ $student->student_number ?? 'TBD' }}
School account email: {{ $student->user?->email ?? 'to follow — ask the Registrar' }}

Sign in to the student portal with the school account email above and your existing password. If your name matched another student's, a number was added to keep every address unique — use the address exactly as shown here. This notice was sent to the student's email on file.
