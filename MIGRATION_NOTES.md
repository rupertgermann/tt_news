# TYPO3 v13 Migration - TypoScriptParser Replacement

## Date: 2025-11-05

## Changes Applied

### File: `Classes/Plugin/TtNews.php`

**Migration:** Replaced deprecated `TypoScriptParser` with `TypoScriptStringFactory` and `AstBuilder`

#### Added Imports (lines 53-55):
```php
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
```

#### Updated Method: `preInit()` (lines 477-489)

**Before:**
```php
$flexformTyposcript = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'myTS', 's_misc');
if ($flexformTyposcript) {
    /** @var TypoScriptParser $tsparser */
    $tsparser = GeneralUtility::makeInstance(TypoScriptParser::class);
    // Copy conf into existing setup
    $tsparser->setup = $this->conf;
    // Parse the new Typoscript
    $tsparser->parse($flexformTyposcript);
    // Copy the resulting setup back into conf
    $this->conf = $tsparser->setup;
}
```

**After:**
```php
$flexformTyposcript = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'myTS', 's_misc');
if ($flexformTyposcript) {
    // Use new TypoScript parser for TYPO3 v13 compatibility
    $typoScriptStringFactory = GeneralUtility::makeInstance(TypoScriptStringFactory::class);
    $astBuilder = GeneralUtility::makeInstance(AstBuilder::class);
    
    // Parse the flexform TypoScript
    $ast = $typoScriptStringFactory->parseFromString($flexformTyposcript, $astBuilder);
    $parsedConfig = $ast->toArray();
    
    // Merge parsed config with existing conf
    $this->conf = array_replace_recursive($this->conf, $parsedConfig);
}
```

## Key Changes

1. **Removed deprecated class:** `TypoScriptParser` (deprecated in TYPO3 v12.2, removed in v13)
2. **Added new classes:** 
   - `TypoScriptStringFactory` - Factory for parsing TypoScript strings
   - `AstBuilder` - Builds Abstract Syntax Tree from TypoScript
3. **Changed parsing approach:**
   - Old: Mutable parser with setup property
   - New: Immutable AST-based parsing with `toArray()` conversion
4. **Changed merge strategy:**
   - Old: Direct property assignment after parsing
   - New: `array_replace_recursive()` to merge configurations

## Testing Recommendations

1. Test flexform TypoScript configuration in tt_news plugin
2. Verify that custom TypoScript from flexforms is properly merged with main configuration
3. Check all display modes (LIST, SINGLE, etc.) to ensure configuration is applied correctly

## References

- [TYPO3 Deprecation Notice #99120](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.2/Deprecation-99120-DeprecateOldTypoScriptParser.html)
- [TypoScriptStringFactory API](https://api.typo3.org/main/classes/TYPO3-CMS-Core-TypoScript-TypoScriptStringFactory.html)
