<?php
namespace Tests\Feature;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ContactConsentTest extends TestCase
{
    use RefreshDatabase;
    private function registerClient(): User
    {
        $this->post('/register',['name'=>'Client Test','company'=>'Entreprise Test','email'=>'client@example.com','password'=>'StrongPass123!','password_confirmation'=>'StrongPass123!','terms'=>'1'])->assertRedirect('/dashboard');
        return User::where('email','client@example.com')->firstOrFail();
    }
    public function test_contact_without_consent_is_saved_but_not_reachable(): void
    {
        $user=$this->registerClient();
        $this->post('/contacts',['name'=>'Contact Test','phone'=>'+237 6 70 00 00 00'])->assertRedirect('/contacts');
        $contact=Contact::where('phone','+237670000000')->firstOrFail();
        $this->assertFalse($contact->consent_given);
        $this->assertSame(0,Contact::where('tenant_id',$user->currentTenant()->id)->reachable()->count());
    }
    public function test_client_cannot_access_another_tenant_contact(): void
    {
        $user=$this->registerClient();
        $other=User::create(['name'=>'Autre','email'=>'other@example.com','password'=>'StrongPass123!']);
        $tenant=Tenant::create(['name'=>'Autre société','slug'=>'autre-societe','status'=>'active','owner_id'=>$other->id]);
        $tenant->users()->attach($other->id,['role'=>'owner','is_current'=>true]);
        $contact=Contact::create(['tenant_id'=>$tenant->id,'created_by'=>$other->id,'phone'=>'+237670000000','consent_given'=>true,'consent_source'=>'formulaire','consented_at'=>now()]);
        $this->delete('/contacts/'.$contact->id)->assertNotFound();
        $this->assertDatabaseHas('contacts',['id'=>$contact->id]);
    }
}
