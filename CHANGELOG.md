# Changelog

## 0.6
- Many bugfixes
- now the $xphpt_current is available in the template
- BEM: content(), $block_elem == 'block elem' in match section
- Added tests: test_content.phpt, test_tpls_list2.phpt, test_common_use_*.phpt

## 0.5.2
- Bugfixes in xphpt_apply(), toHtml()
- Improved error messages
- BEM: 'tag' only mode
- Added tests: test_tag_only1.phpt

## 0.5.1
- Bugfixes in toHtml()
- Added before_html and after_html fields in bem_array
- Added tests: test_toHtml1.phpt, test_inheritance_blockname1.phpt, dir1/dir2/test1.phpt and imported test framewark run-tests.php

## 0.5
- Fully refactored.
- Added applyCtx(), applyNext(), toHtml() for BEM mode.
- Added apply_templates() and call_template() for XSLT mode.
- Added tests.

## 0.1
- First simple working version.