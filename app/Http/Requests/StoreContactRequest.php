<?php

namespace App\Http\Requests;

use App\Support\LeadAttribution;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    /**
     * Offer choices accepted by the contact form ("Conseil" = wants advice).
     *
     * @var list<string>
     */
    public const OFFERS = ['Silver', 'Golden', 'Diamond', 'Conseil'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            // "strict" also rejects addresses the mailer cannot use (comments, quoted local parts).
            'email' => ['required', 'string', 'email:strict', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9+().\s-]{8,30}$/'],
            'city' => ['required', 'string', Rule::in(['Marrakech', 'Casablanca'])],
            'offer' => ['nullable', 'string', Rule::in(self::OFFERS)],
            'message' => ['nullable', 'string', 'max:2000'],
            // Generated when the form is rendered; pages rendered before it existed send none.
            'submission_token' => ['nullable', 'uuid'],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Veuillez indiquer votre nom complet.',
            'name.min' => 'Le nom complet doit contenir au moins 2 caractères.',
            'name.max' => 'Le nom complet ne peut pas dépasser 100 caractères.',
            'email.required' => 'Veuillez indiquer votre adresse e-mail.',
            'email.email' => 'Veuillez indiquer une adresse e-mail valide.',
            'email.max' => 'L’adresse e-mail ne peut pas dépasser 255 caractères.',
            'phone.required' => 'Veuillez indiquer votre numéro de téléphone.',
            'phone.regex' => 'Veuillez indiquer un numéro de téléphone valide.',
            'city.required' => 'Veuillez choisir une ville.',
            'city.in' => 'La ville choisie doit être Marrakech ou Casablanca.',
            'offer.in' => 'Veuillez choisir une offre proposée.',
            'message.max' => 'Votre message ne peut pas dépasser 2 000 caractères.',
            'submission_token.uuid' => 'Le formulaire a expiré. Veuillez actualiser la page et réessayer.',
        ];
    }

    /**
     * Token identifying this form submission, so a resubmission is not stored twice.
     */
    public function submissionToken(): string
    {
        return $this->validated('submission_token') ?? (string) Str::uuid();
    }

    /**
     * First-touch attribution of the visitor's session, to save with the request.
     *
     * @return array<string, string|null>
     */
    public function attribution(): array
    {
        return LeadAttribution::fromSession($this->session());
    }

    protected function getRedirectUrl(): string
    {
        return route('home').'#contact';
    }
}
