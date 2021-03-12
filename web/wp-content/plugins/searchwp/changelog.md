### 4.1.0
- **[IMPORTANT]** Comments are now a separate Source (if you are using Comments for any Post Type Source you *will need to edit your Engine and rebuld your index*, this is not done automatically and should be planned for when updating)
- **[New]** Refactored indexer for stability, reliability, and performance optimization
- **[New]** `searchwp\index\update_entry` action when an Entry is updated in the Index
- **[New]** `searchwp\debug\front_end_summary` filter to control whether the HTML comment summary is output when debugging is enabled
- **[New]** `wp searchwp reindex` WP-CLI command
- **[New]** `wp searchwp diagnostics` WP-CLI command
- **[New]** Primary keys added to database tables *for new installs* where previously there were none
- **[New]** Adds `$query` property to `\SWP_Query` to reference underlying query object
- **[New]** `searchwp\source\comment\parent_attribution\strict` hook to control whether SearchWP is strict about parent attribution which can introduce overhead in some cases
- **[New]** SearchWP will now generate more expected variations of regex pattern matches
- **[New]** `searchwp\tokens\generate_parttern_match_variations` filter to control whether additional pattern match variations are generated during indexing
- **[New]** Ability to enter custom ignored queries for Statistics (with wildcard `*` support)
- **[New]** Ability to automatically trim Statistics logs
- **[New]** Notice when WordPress available memory can be improved
- **[New]** Export/import now optionally includes Settings, Stopwords, and Synonyms
- **[New]** All ACF fields are displayed in the Custom Fields dropdown when applicable, not only ACF "Repeatables"
- **[New]** Synonyms actions: Sort ASC, Sort DESC, Clear
- **[Change]** Refines default data indexed for Taxonomy Terms to be: taxonomy name, term name, term slug, and term description
- **[Change]** Abstracts Statistics Dashboard Widget from `jquery-ui-tabs`
- **[Improvement]** Indexer auto-scaling when server load is high
- **[Improvement]** Handling of HTML-formed content during indexing
- **[Improvement]** Integration of partial matches and keyword stemming
- **[Improvement]** Indexer delta trigger specificity in some cases
- **[Improvement]** Document processing handling, footprint
- **[Improvement]** Highlighting when a suggested search has been returned
- **[Fix]** Prevent duplicate results during weight transfer in some cases
- **[Fix]** `AND` logic restriction, performance in some cases
- **[Fix]** Persistent dismissal of missing integration notice
- **[Fix]** Synonyms management in some cases
- **[Update]** Dependencies
- **[Update]** Translation source
- **[Update]** Interface updates and refinements

### 4.0.34
- **[Fix]** Fixes regression introduced by Shortcodes fix in `4.0.33` (proper fix in Shortcodes `1.8.2`)
- **[Update]** Updated updater

### 4.0.33
- **[New]** `searchwp\swp_query\mods` filter to add Mods to `SWP_Query`
- **[Fix]** PHP Warning introduced in 4.0.32
- **[Fix]** Prevention of duplicate indexing processes in some cases
- **[Fix]** Display issue on Statistics screen
- **[Fix]** PHP Warning when Admin color schemes have been removed
- **[Fix]** PDF parsing taking place unnecssarily in some cases
- **[Improvement]** Disable `searchwp_search_results` Shortcode when generating excerpts so as to prevent unwanted loop

### 4.0.32
- **[Fix]** Duplicate indexer processes in some cases
- **[Fix]** Inaccurate batch size handling in some cases
- **[Fix]** Prevent redundant search suggestion output in some cases
- **[Improvement]** Post stati validation when parent attribution is enabled
- **[Improvement]** Attachment status handling over time
- **[New]** `RAND(seed)` support by suffixing `random` `Mod` `order_by` with a colon and seed e.g. `random:10` will be `RAND(10)`

### 4.0.31
- **[Improvement]** Adds `NOT IN` option to Media File Type Rule
- **[Improvement]** Debug HTML comment block output during Admin requests
- **[Fix]** Custom Attribute Options not returning proper Label after saving
- **[Fix]** Relocate `searchwp\query\search_string` hook to fire earlier
- **[Fix]** Issue when performing cross-site Multisite search
- **[Fix]** `searchwp\query\tokens\limit` default value
- **[New]** Action `searchwp\query\core_mods_out_of_bounds` fires when core Mods are considered out of bounds

### 4.0.30
- **[Fix]** UI changes introduced by WordPress 5.5
- **[Fix]** Global excerpt generation from search suggestions
- **[Fix]** Synonym migration from SearchWP 3.x

### 4.0.29
- **[Fix]** Token handling in some cases
- **[Fix]** Document content handling when using alternate indexer in some cases
- **[Improvement]** Tokenization of HTML in some cases
- **[New]** `searchwp\entry\update_data\before` action fired before `Entry` data is retrieved
- **[Update]** Bundle dependencies

### 4.0.28
- **[Fix]** Prevent inapplicable comment edit events from triggering delta updates
- **[Improvement]** Reduced index method checks
- **[Improvement]** Reactivity when observing meta updates

### 4.0.27
- **[Fix]** File Content meta box display in some cases
- **[Fix]** Entries not being reintroduced after failing when using alternate indexer
- **[Fix]** Display of Source Attribute Options when statically defined
- **[Fix]** UI display edge cases
- **[Change]** Token handling chunked in more cases so as to avoid issues when hosts limit query character length

### 4.0.26
- **[Fix]** Handling of `SWP_Query` `tax_query` argument
- **[New]** Advanced setting checkbox to control whether stored document content is purged and re-indexed during index rebuilds
- **[Update]** Translation source

### 4.0.25
- **[Fix]** Regression introduced in 4.0.24 when utilizing PDF Metadata
- **[Improvement]** Note displayed in SearchWP Document Content meta box when document is queued but not yet processed
- **[Update]** Translation source

### 4.0.24
- **[Fix]** Handling of PDF metadata that includes invalid characters
- **[Fix]** Searching of hierarchical post types in the Admin
- **[Improvement]** Performance when handling documents outside the indexing process
- **[Update]** Bundle dependencies

### 4.0.23
- **[Fix]** Utilize previously extracted PDF metadata instead of parsing it repeatedly
- **[Change]** Updated default batch size for Media to 3, can be customized with `searchwp\indexer\batch_size\post.attachment` hook
- **[Improvement]** Handling of urlencoded tokens in some cases

### 4.0.22
- **[New]** Query parameter support for `post_type` when using `SWP_Query` (additional parameter support is planned)
- **[Fix]** Issue with partial matching yielding zero results in some cases
- **[Fix]** Quoted search support for `WP_Post` Content, Document Content
- **[Improvement]** Reduced debug log volume (logs should be deleted once you're done debugging)

### 4.0.20
- **[New]** New filter `searchwp\source\post\db_where` to customize global `WHERE` limits per post type
- **[New]** License key is automatically activated when provided via constant or hook
- **[Fix]** Error on uninstall when removing all data
- **[Fix]** Issue where Mods were not applied to `SWP_Query` in some cases
- **[Change]** No longer relying on `excerpt_more` when working with excerpts, now using ellipsis filtered by `searchwp\utils\excerpt_more`
- **[Improvement]** Handling of rare cases where index would need to be woken up repeatedly in order to build
- **[Improvement]** Omits redundant Entry retrieval in some cases
- **[Improvement]** Significant performance retrieval when generating excerpts (e.g. Highlighting)
- **[Improvement]** Advanced Custom Fields integration support

### 4.0.19
- **[Notice]** `Mod`s have in part been cleaned up and refined in this release, which may affect your usage. Please review any `Mod`s you are using by testing this update on a staging server. If you are manipulating relevance weight based on date, it is likely you will need to update your hooks. Snippets have been updated on the KB article [https://searchwp.com/?p=222848](https://searchwp.com/?p=222848) for review. Please also ensure your SearchWP Extensions are up to date as well.
- **[Fix]** Source `Mod` `WHERE` clauses causing errors in some cases
- **[Fix]** Raw `Mod` `WHERE` clauses had no local alias to utilize
- **[Fix]** `Mod` `JOIN` claus order was not retained causing errors in some cases
- **[Improvement]** Optimized `Mod` handling in `SWP_Query`
- **[Improvement]** Disable integration extension checks when doing AJAX

### 4.0.18
- **[Fix]** Error when using `mod` argument of `\SearchWP\Query` parameters array
- **[Improvement]** Control over Settings page navigation

### 4.0.17
- **[Note]** Rebuilding your index using the Rebuild Index button on the Engines tab of the SearchWP settings screen is recommended after updating
- **[Fix]** Delta update regression introduced in `4.0.13`
- **[Fix]** Error when applying delta update to Source that no longer exists
- **[Improvement]** Handling of delta update process during failures
- **[Improvement]** Delta update queue handling during index rebuild

### 4.0.16
- **[Fix]** Invalid range in character class introduced in 4.0.15 for PHP 7.3+
- **[Change]** `searchwp\tokens\whitelist\only_full_matches` retagged as `searchwp\tokens\regex_patterns\only_full_matches`
- **[Change]** `searchwp\tokens\apply_rules_to_whitelist` retagged as `searchwp\tokens\apply_rules_to_pattern_matches`
- **[Change]** `searchwp\tokens\whitelist_regex_patterns` retagged as `searchwp\tokens\regex_patterns`
- **[Change]** Regex pattern matches are now tokenized during indexing (but remain exclusive when searching by default when applicable)
- **[New]** Filter `searchwp\tokens\tokenize_pattern_matches\indexing` to disable new tokenizing of pattern match behavior during indexing

### 4.0.15
- **[New]** New filter `searchwp\tokens\string` to customize strings before tokenization
- **[Fix]** Handling of synonyms when finding partial matches
- **[Fix]** Implementation and handling of regex pattern match tokenization setting
- **[Improvement]** Dash/hyphen and word match regex patterns
- **[Improvement]** `searchwp\source\post\excerpt_haystack` filter now passes arguments array
- **[Update]** Translation source

### 4.0.14
- **[Fix]** Issue where partial matches from keyword stems were not found in some cases
- **[Fix]** Partial match token processing limited to applicable site(s)
- **[Fix]** Excerpt generation when handling unregistered Shortcodes

### 4.0.13
- **[Fix]** Delta update routine when using alternate indexer that caused unwanted exit
- **[Fix]** `searchwp\document\content` implementation
- **[Improvement]** Index integrity check when rebuilding
- **[Improvement]** Source hook management in multisite
- **[Improvement]** Upgrade routine process

### 4.0.12
- **[Fix]** Inability to filter `searchwp\settings\capability`
- **[Fix]** Issue with Highlighting in some cases
- **[Fix]** Document Content not properly considered for global excerpt in some cases
- **[Fix]** Warning when reacting to invalid `Entry` during indexing
- **[Fix]** Namespace issue with PDF parsing in some cases
- **[Fix]** Unnecessary provider reset when switching to the same site in multisite
- **[Update]** Updated updater

### 4.0.11
- **[Fix]** Loss of tokens when applying partial match logic in some cases
- **[Update]** Revised MySQL minimum to 5.6 because of `utf8mb4_unicode_520_ci` collation requirement

### 4.0.9
- **[Fix]** Regression introduced in 4.0.6 that prevented non `WP_Post` results from returning

### 4.0.8
- **[Fix]** Issue where taxonomy Rules for Media were not applied correctly in some cases

### 4.0.7
- **[Fix]** Mod `WHERE` clauses not restricted to `Source` when defined

### 4.0.6
- **[Change]** Post is now returned when parent weight transfer is enabled but Post has no `post_parent`
- **[Improvement]** Excerpt handling for native results
- **[Improvement]** Additional prevention of invalid `WP_Post` results being returned in one case

### 4.0.5
- **[New]** Filter to control stemmer locale `searchwp\stemmer\locale`
- **[Improvement]** Token stems/partial matches are considered during `AND` logic pass
- **[Fix]** String not sent to `searchwp\stemmer\custom`
- **[Change]** `searchwp\query\partial_matches\buoy` is now opt-in

### 4.0.4
- **[Fix]** Issue where `AND` logic would not apply in some cases
- **[Fix]** Issue where additional unnecessary query clauses are added in some cases
- **[Fix]** Issue with delta updates not processing when HTTP Basic Auth is active
- **[Fix]** Minimum PHP version requirement check (which is 7.2)

### 4.0.3
- **[Fix]** Issue where tokens table was not reset during index rebuild

### 4.0.2
- **[New]** Support for `BETWEEN`, `NOT BETWEEN`, `LIKE`, and `NOT LIKE` compare operators for `Mod` `WHERE` clauses
- **[Fix]** Handling of `Mod` `WHERE` clauses in some cases
- **[Fix]** Handling of REST parameters when returning search results

### 4.0.1
- **[New]** Check for remnants of SearchWP 3 that were not removed as per the Migration Guide
- **[New]** `searchwp\source\post\attributes\comments` action when retrieving Post comments
- **[Fix]** Handling of empty search strings in some cases

### 4.0.0
- **[New]** Complete rewrite of SearchWP
