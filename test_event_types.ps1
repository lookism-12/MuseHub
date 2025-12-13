# Script de test pour EventType API
# Utilisation: .\test_event_types.ps1

$baseUrl = "http://localhost/MuseHub-my-work/MuseHub-my-work/public/api"

Write-Host "`n🧪 TESTS EventType API`n" -ForegroundColor Cyan

# Test 1: Liste tous les types
Write-Host "📋 Test 1: Liste tous les types d'événements" -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "$baseUrl/event-types" -Method GET
Write-Host "✅ Nombre de types: $($response.count)" -ForegroundColor Green
$response.data | ForEach-Object {
    Write-Host "   $($_.icon) $($_.name) - $($_.color)" -ForegroundColor White
}

# Test 2: Types payants
Write-Host "`n💰 Test 2: Types d'événements payants" -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "$baseUrl/event-types?paid=true" -Method GET
Write-Host "✅ Types payants: $($response.count)" -ForegroundColor Green
$response.data | ForEach-Object {
    Write-Host "   $($_.icon) $($_.name) - Prix requis: $($_.requires_payment)" -ForegroundColor White
}

# Test 3: Types avec capacité limitée
Write-Host "`n👥 Test 3: Types avec capacité limitée" -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "$baseUrl/event-types?capacity_type=limited" -Method GET
Write-Host "✅ Types limités: $($response.count)" -ForegroundColor Green
$response.data | ForEach-Object {
    Write-Host "   $($_.icon) $($_.name) - Max: $($_.default_max_participants) participants" -ForegroundColor White
}

# Test 4: Détails d'un type spécifique
Write-Host "`n🔍 Test 4: Détails du Workshop" -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "$baseUrl/event-types/2" -Method GET
$type = $response.data
Write-Host "   Nom: $($type.name)" -ForegroundColor White
Write-Host "   Description: $($type.description)" -ForegroundColor White
Write-Host "   Durée typique: $($type.typical_duration_hours)h" -ForegroundColor White
Write-Host "   Certificat: $(if($type.certificate_enabled){'Oui'}else{'Non'})" -ForegroundColor White

# Test 5: Statistiques
Write-Host "`n📊 Test 5: Statistiques globales" -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "$baseUrl/event-types/stats/summary" -Method GET
$stats = $response.data
Write-Host "✅ Total: $($stats.total)" -ForegroundColor Green
Write-Host "   Actifs: $($stats.active)" -ForegroundColor White
Write-Host "   Payants: $($stats.paid)" -ForegroundColor White
Write-Host "   Gratuits: $($stats.free)" -ForegroundColor White
Write-Host "   Avec certificat: $($stats.with_certificate)" -ForegroundColor White
Write-Host "`n   Capacités:" -ForegroundColor White
Write-Host "   - Illimitée: $($stats.by_capacity_type.unlimited)" -ForegroundColor Gray
Write-Host "   - Limitée: $($stats.by_capacity_type.limited)" -ForegroundColor Gray
Write-Host "   - Invitation: $($stats.by_capacity_type.invite_only)" -ForegroundColor Gray
Write-Host "`n   Localisation:" -ForegroundColor White
Write-Host "   - Online: $($stats.by_location.online)" -ForegroundColor Gray
Write-Host "   - Offline: $($stats.by_location.offline)" -ForegroundColor Gray
Write-Host "   - Les deux: $($stats.by_location.both)" -ForegroundColor Gray

Write-Host "`n✅ Tous les tests terminés avec succès!`n" -ForegroundColor Green
