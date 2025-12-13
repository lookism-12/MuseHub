# Guide to Merge Community Module with Main Branch

This guide explains how to merge your community module work into the main branch.

## Current Situation

You have three branches:
- **`master`** - Your main branch
- **`restore-work`** - Your working branch with community module
- **`community-module`** - Branch you just pushed to testing repo

## Option 1: Merge community-module into master (Recommended)

This is the cleanest approach if you want to keep your community module work in the main branch.

### Steps:

```bash
# 1. Switch to master branch
git checkout master

# 2. Make sure master is up to date
git pull origin master

# 3. Merge community-module into master
git merge community-module

# 4. If there are conflicts, resolve them (see "Resolving Conflicts" section below)

# 5. Push the merged changes to origin
git push origin master
```

## Option 2: Merge restore-work into master

If `restore-work` has all your latest work including the community module:

```bash
# 1. Switch to master
git checkout master

# 2. Pull latest changes
git pull origin master

# 3. Merge restore-work into master
git merge restore-work

# 4. Resolve any conflicts if needed

# 5. Push to origin
git push origin master
```

## Option 3: Create a Pull Request (GitHub Workflow)

If you want to review changes before merging:

### On GitHub:

1. Go to your main repository (not the testing one)
2. Click "Pull Requests" → "New Pull Request"
3. Set:
   - **Base**: `master`
   - **Compare**: `community-module` or `restore-work`
4. Review the changes
5. Click "Create Pull Request"
6. Add description and submit
7. Merge the PR when ready

### Locally after PR is merged:

```bash
# Update your local master
git checkout master
git pull origin master
```

## Option 4: Rebase (Advanced - Clean History)

If you want a cleaner commit history:

```bash
# 1. Switch to community-module
git checkout community-module

# 2. Rebase onto master
git rebase master

# 3. Resolve conflicts if any

# 4. Switch to master
git checkout master

# 5. Fast-forward merge
git merge community-module

# 6. Push to origin
git push origin master
```

## Resolving Conflicts

If you encounter merge conflicts:

### 1. Check which files have conflicts:
```bash
git status
```

### 2. Open conflicted files
Look for conflict markers:
```
<<<<<<< HEAD
(current branch code)
=======
(incoming branch code)
>>>>>>> community-module
```

### 3. Resolve each conflict
- Keep the code you want
- Remove conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`)
- Save the file

### 4. Mark as resolved:
```bash
git add <resolved-file>
```

### 5. Complete the merge:
```bash
git commit
```

## Recommended Workflow for Your Situation

Based on your setup, here's what I recommend:

### Step 1: Backup Current Work
```bash
# Create a backup branch just in case
git branch backup-before-merge
```

### Step 2: Merge into Master
```bash
# Switch to master
git checkout master

# Merge your community module work
git merge community-module -m "Merge community module: posts, comments, notifications, and categories"
```

### Step 3: Verify Everything Works
```bash
# Check that all files are present
git status

# Run your application to test
# php bin/console doctrine:migrations:migrate
# php bin/console cache:clear
```

### Step 4: Push to Origin
```bash
# Push the merged master branch
git push origin master
```

### Step 5: Clean Up (Optional)
```bash
# Delete the community-module branch if no longer needed
git branch -d community-module

# Delete remote branch on testing repo (optional)
git push testing --delete community-module
```

## Verifying the Merge

After merging, verify that all community module files are present:

```bash
# Check that community files exist
ls src/Entity/Community.php
ls src/Entity/Post.php
ls src/Controller/CommunityController.php
ls templates/community/

# Check git log
git log --oneline -5

# Check all branches
git branch -a
```

## Common Issues and Solutions

### Issue 1: "Already up to date"
**Cause**: The branches have the same commits.
**Solution**: Your work is already merged, no action needed.

### Issue 2: Merge Conflicts
**Cause**: Same files modified in both branches.
**Solution**: Follow "Resolving Conflicts" section above.

### Issue 3: Lost Commits
**Cause**: Incorrect merge or reset.
**Solution**: Use your backup branch:
```bash
git checkout backup-before-merge
git branch -D master
git checkout -b master
```

### Issue 4: Want to Undo Merge
**Before pushing:**
```bash
git reset --hard HEAD~1
```

**After pushing:**
```bash
git revert -m 1 HEAD
git push origin master
```

## Syncing All Your Work

If you want to ensure all branches have the same community module work:

```bash
# 1. Merge into master
git checkout master
git merge community-module

# 2. Update restore-work
git checkout restore-work
git merge master

# 3. Push all branches
git push origin master
git push origin restore-work
```

## Best Practice Going Forward

After merging, establish a clear workflow:

1. **Main Development**: Work on `master` or create feature branches
2. **Feature Branches**: Create branches like `feature/new-feature`
3. **Merge to Master**: Merge features when complete
4. **Testing**: Use separate repos (like your testing repo) for school projects

### Example Workflow:
```bash
# Create feature branch
git checkout -b feature/user-profiles

# Work on feature
git add .
git commit -m "Add user profiles"

# Merge to master when done
git checkout master
git merge feature/user-profiles

# Delete feature branch
git branch -d feature/user-profiles
```

## Quick Reference Commands

```bash
# View all branches
git branch -a

# View branch differences
git diff branch1..branch2

# View commit history
git log --oneline --graph --all

# Check current branch
git branch

# Switch branches
git checkout <branch-name>

# Merge branch into current
git merge <branch-name>

# Abort merge if conflicts are too complex
git merge --abort

# See what would be merged (without merging)
git merge --no-commit --no-ff <branch-name>
git merge --abort  # to cancel the preview
```

## Summary

**Recommended approach for you:**

1. ✅ Backup: `git branch backup-before-merge`
2. ✅ Switch: `git checkout master`
3. ✅ Merge: `git merge community-module`
4. ✅ Test: Make sure everything works
5. ✅ Push: `git push origin master`

This will bring all your community module work into your main branch!
