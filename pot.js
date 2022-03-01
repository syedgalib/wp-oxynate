const wpPot = require('wp-pot');
 
wpPot({
  destFile: './languages/wp-oxynate.pot',
  domain: 'wp-oxynate',
  package: 'Simple Todo',
  src: './**/*.php'
});