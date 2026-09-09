# Repository agent instructions

## Git workflow

- Preserve the current branch for implementation, commits, and pushes.
- When the user asks to commit or push, use the current branch unless they explicitly name another branch or request a pull-request branch.
- Create, switch, rename, or delete branches only when the user explicitly asks.
- Before committing, inspect `git status` and the relevant diffs.
- Stage only paths the user has confirmed; preserve unrelated uncommitted changes and ask before including them.
- After publishing, report the branch name, commit, and remote result.

## remote depolyment
- use the ssh credentials defined in .env and login and change directory to the website root directory and execute bash deploy.sh 


use \public\images\product-options\business-cards\swatches\standard-size.webp as standard size swatch image
use \public\images\product-options\business-cards\swatches\square-size.webp as square size swatch image
for all business card products
and don't ever change that again
