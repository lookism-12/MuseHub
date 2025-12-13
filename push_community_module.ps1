# PowerShell Script to Push Community Module to Testing Repository
# This script creates a new branch with only community module files

Write-Host "=== Community Module Git Push Script ===" -ForegroundColor Cyan
Write-Host ""

# Configuration
$testingRepoUrl = "https://github.com/oussama0bks/musehub2.git"
$branchName = "community-module"

# Community module files to include
$communityFiles = @(
    # Entities
    "src/Entity/Community.php",
    "src/Entity/Post.php",
    "src/Entity/PostCategory.php",
    "src/Entity/PostReaction.php",
    "src/Entity/Comment.php",
    "src/Entity/Notification.php",
    
    # Controllers
    "src/Controller/CommunityController.php",
    "src/Controller/CommunityApiController.php",
    "src/Controller/CommunityDashboardController.php",
    "src/Controller/PostController.php",
    "src/Controller/PostAdminController.php",
    "src/Controller/WebPostController.php",
    "src/Controller/CommentController.php",
    "src/Controller/NotificationController.php",
    
    # Repositories
    "src/Repository/CommunityRepository.php",
    "src/Repository/PostRepository.php",
    "src/Repository/PostCategoryRepository.php",
    "src/Repository/PostReactionRepository.php",
    "src/Repository/CommentRepository.php",
    "src/Repository/NotificationRepository.php",
    
    # Services
    "src/Service/NotificationService.php",
    
    # Forms
    "src/Form/CommunityType.php",
    
    # Templates - Community
    "templates/community/admin.html.twig",
    "templates/community/admin_form.html.twig",
    "templates/community/admin_list.html.twig",
    "templates/community/edit.html.twig",
    "templates/community/index.html.twig",
    "templates/community/new.html.twig",
    "templates/community/show.html.twig",
    
    # Templates - Post
    "templates/post/admin.html.twig",
    "templates/post/index.html.twig",
    "templates/post/show.html.twig",
    
    # Templates - Front
    "templates/front/community.html.twig",
    
    # Migrations
    "migrations/Version20251120120000.php",
    "migrations/Version20251201210000.php",
    "migrations/Version20251201211000.php",
    "migrations/Version20251202000000.php",
    
    # Documentation
    "COMMUNITY_MODULE_FILES.md"
)

# Step 1: Check if we're in a git repository
Write-Host "Step 1: Checking git repository..." -ForegroundColor Yellow
if (-not (Test-Path ".git")) {
    Write-Host "Error: Not in a git repository. Please run this script from the project root." -ForegroundColor Red
    exit 1
}
Write-Host "✓ Git repository found" -ForegroundColor Green
Write-Host ""

# Step 2: Check if files exist
Write-Host "Step 2: Verifying community module files..." -ForegroundColor Yellow
$missingFiles = @()
$existingFiles = @()

foreach ($file in $communityFiles) {
    if (Test-Path $file) {
        $existingFiles += $file
        Write-Host "  ✓ $file" -ForegroundColor Green
    } else {
        $missingFiles += $file
        Write-Host "  ✗ $file (missing)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Found $($existingFiles.Count) files, $($missingFiles.Count) missing" -ForegroundColor Cyan
Write-Host ""

if ($existingFiles.Count -eq 0) {
    Write-Host "Error: No community module files found!" -ForegroundColor Red
    exit 1
}

# Step 3: Ask for confirmation
Write-Host "Step 3: Ready to push to testing repository" -ForegroundColor Yellow
Write-Host "  Repository: $testingRepoUrl" -ForegroundColor Cyan
Write-Host "  Branch: $branchName" -ForegroundColor Cyan
Write-Host "  Files to push: $($existingFiles.Count)" -ForegroundColor Cyan
Write-Host ""

$confirmation = Read-Host "Do you want to continue? (yes/no)"
if ($confirmation -ne "yes") {
    Write-Host "Operation cancelled." -ForegroundColor Yellow
    exit 0
}

# Step 4: Add remote if it doesn't exist
Write-Host ""
Write-Host "Step 4: Setting up remote repository..." -ForegroundColor Yellow
$remotes = git remote
if ($remotes -contains "testing") {
    Write-Host "  Remote 'testing' already exists, updating URL..." -ForegroundColor Cyan
    git remote set-url testing $testingRepoUrl
} else {
    Write-Host "  Adding remote 'testing'..." -ForegroundColor Cyan
    git remote add testing $testingRepoUrl
}
Write-Host "✓ Remote configured" -ForegroundColor Green
Write-Host ""

# Step 5: Create and checkout new branch
Write-Host "Step 5: Creating branch '$branchName'..." -ForegroundColor Yellow
$currentBranch = git rev-parse --abbrev-ref HEAD
Write-Host "  Current branch: $currentBranch" -ForegroundColor Cyan

# Check if branch already exists
$branchExists = git branch --list $branchName
if ($branchExists) {
    Write-Host "  Branch '$branchName' already exists. Switching to it..." -ForegroundColor Cyan
    git checkout $branchName
} else {
    Write-Host "  Creating new branch '$branchName'..." -ForegroundColor Cyan
    git checkout -b $branchName
}
Write-Host "✓ Branch ready" -ForegroundColor Green
Write-Host ""

# Step 6: Add community module files
Write-Host "Step 6: Adding community module files..." -ForegroundColor Yellow
foreach ($file in $existingFiles) {
    git add $file
    Write-Host "  Added: $file" -ForegroundColor Green
}
Write-Host "✓ Files staged" -ForegroundColor Green
Write-Host ""

# Step 7: Commit changes
Write-Host "Step 7: Committing changes..." -ForegroundColor Yellow
$commitMessage = "Add community module for school project

This commit includes:
- Community management system
- Post system with categories
- Post reactions (likes/dislikes)
- Threaded comment system
- Notification system
- Admin dashboard

Total files: $($existingFiles.Count)"

git commit -m $commitMessage
Write-Host "✓ Changes committed" -ForegroundColor Green
Write-Host ""

# Step 8: Push to testing repository
Write-Host "Step 8: Pushing to testing repository..." -ForegroundColor Yellow
Write-Host "  Pushing branch '$branchName' to 'testing' remote..." -ForegroundColor Cyan
git push -u testing $branchName

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Successfully pushed to testing repository!" -ForegroundColor Green
    Write-Host ""
    Write-Host "=== Summary ===" -ForegroundColor Cyan
    Write-Host "Repository: $testingRepoUrl" -ForegroundColor White
    Write-Host "Branch: $branchName" -ForegroundColor White
    Write-Host "Files pushed: $($existingFiles.Count)" -ForegroundColor White
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Visit: https://github.com/oussama0bks/musehub2" -ForegroundColor White
    Write-Host "2. Create a Pull Request from '$branchName' branch" -ForegroundColor White
    Write-Host "3. Review and merge for your school project" -ForegroundColor White
    Write-Host ""
    Write-Host "To return to your original branch, run:" -ForegroundColor Yellow
    Write-Host "  git checkout $currentBranch" -ForegroundColor Cyan
} else {
    Write-Host "✗ Error pushing to repository" -ForegroundColor Red
    Write-Host "Please check your credentials and try again" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Script Complete ===" -ForegroundColor Cyan
