# Repository agent instructions

## Git workflow

- Preserve the current branch for implementation, commits, and pushes.
- When the user asks to commit or push, use the current branch unless they explicitly name another branch or request a pull-request branch.
- Create, switch, rename, or delete branches only when the user explicitly asks.
- Before committing, inspect `git status` and the relevant diffs.
- Stage only paths the user has confirmed; preserve unrelated uncommitted changes and ask before including them.
- After publishing, report the branch name, commit, and remote result.
