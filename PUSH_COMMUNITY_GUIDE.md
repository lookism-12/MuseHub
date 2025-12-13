# Manual Git Commands to Push Community Module

This document provides step-by-step git commands to manually push only the community module files to your testing repository.

## Prerequisites
- Make sure you're in the project root directory
- Ensure you have git installed and configured

## Option 1: Using the PowerShell Script (Recommended)

Simply run the provided script:
```powershell
.\push_community_module.ps1
```

## Option 2: Manual Git Commands

### Step 1: Add the testing repository as a remote
```bash
git remote add testing https://github.com/oussama0bks/musehub2.git
```

If the remote already exists, update it:
```bash
git remote set-url testing https://github.com/oussama0bks/musehub2.git
```

### Step 2: Create a new branch for the community module
```bash
git checkout -b community-module
```

### Step 3: Add only community module files

#### Add Entities
```bash
git add src/Entity/Community.php
git add src/Entity/Post.php
git add src/Entity/PostCategory.php
git add src/Entity/PostReaction.php
git add src/Entity/Comment.php
git add src/Entity/Notification.php
```

#### Add Controllers
```bash
git add src/Controller/CommunityController.php
git add src/Controller/CommunityApiController.php
git add src/Controller/CommunityDashboardController.php
git add src/Controller/PostController.php
git add src/Controller/PostAdminController.php
git add src/Controller/WebPostController.php
git add src/Controller/CommentController.php
git add src/Controller/NotificationController.php
```

#### Add Repositories
```bash
git add src/Repository/CommunityRepository.php
git add src/Repository/PostRepository.php
git add src/Repository/PostCategoryRepository.php
git add src/Repository/PostReactionRepository.php
git add src/Repository/CommentRepository.php
git add src/Repository/NotificationRepository.php
```

#### Add Services
```bash
git add src/Service/NotificationService.php
```

#### Add Forms
```bash
git add src/Form/CommunityType.php
```

#### Add Templates
```bash
git add templates/community/
git add templates/post/
git add templates/front/community.html.twig
```

#### Add Migrations
```bash
git add migrations/Version20251120120000.php
git add migrations/Version20251201210000.php
git add migrations/Version20251201211000.php
git add migrations/Version20251202000000.php
```

#### Add Documentation
```bash
git add COMMUNITY_MODULE_FILES.md
```

### Step 4: Commit the changes
```bash
git commit -m "Add community module for school project

This commit includes:
- Community management system
- Post system with categories
- Post reactions (likes/dislikes)
- Threaded comment system
- Notification system
- Admin dashboard"
```

### Step 5: Push to the testing repository
```bash
git push -u testing community-module
```

### Step 6: Create a Pull Request
1. Go to https://github.com/oussama0bks/musehub2
2. You should see a prompt to create a Pull Request for the `community-module` branch
3. Click "Compare & pull request"
4. Add a description of your community module
5. Submit the Pull Request

### Step 7: Return to your original branch (optional)
```bash
git checkout main
# or
git checkout master
# or whatever your main branch is called
```

## Option 3: Using Git Subtree (Advanced)

If you want to maintain the community module as a separate entity:

```bash
# Create a new orphan branch (no history)
git checkout --orphan community-module-only

# Remove all files from staging
git rm -rf .

# Add only community files
git add src/Entity/Community.php src/Entity/Post.php src/Entity/PostCategory.php src/Entity/PostReaction.php src/Entity/Comment.php src/Entity/Notification.php
git add src/Controller/Community*.php src/Controller/Post*.php src/Controller/Comment*.php src/Controller/Notification*.php
git add src/Repository/Community*.php src/Repository/Post*.php src/Repository/Comment*.php src/Repository/Notification*.php
git add src/Service/NotificationService.php
git add src/Form/CommunityType.php
git add templates/community/ templates/post/ templates/front/community.html.twig
git add migrations/Version20251120120000.php migrations/Version20251201210000.php migrations/Version20251201211000.php migrations/Version20251202000000.php

# Commit
git commit -m "Community module for school project"

# Push to testing repository
git push testing community-module-only:main
```

## Troubleshooting

### Authentication Issues
If you encounter authentication issues:
1. Make sure you have access to the repository
2. Use a Personal Access Token instead of password
3. Configure git credentials:
   ```bash
   git config --global credential.helper wincred
   ```

### Branch Already Exists
If the branch already exists remotely:
```bash
git push testing community-module --force
```
⚠️ Use `--force` with caution!

### Checking What Will Be Pushed
Before pushing, verify the files:
```bash
git diff --name-only testing/community-module
```

Or see the commit:
```bash
git show
```

## Verification

After pushing, verify on GitHub:
1. Go to https://github.com/oussama0bks/musehub2
2. Switch to the `community-module` branch
3. Verify that only community-related files are present
4. Check the commit history

## Notes

- This approach creates a clean branch with only community module files
- The testing repository will receive only the files you specify
- Your original repository remains unchanged
- You can continue working on your main project separately
