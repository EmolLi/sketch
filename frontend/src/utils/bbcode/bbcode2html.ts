// a converter that transforms bbcode to html
// TODO: the converter works by identifying bbcode tags and replace each tags with html. It's possible we will go throught the same text multiple times. This can be inefficient, probably we should build an AST tree first.

const options = {
  showQuotePrefix: false,
  classPrefix: 'ql-',   // class prefix required by quill.js
  mentionPrefix: '@',   // not supported yet
  preserveSpace: true,  // spaces tapped by user will not be striped
};

const codeTags = {
  code: 'code',
  java: 'java',
  javascript: 'javascript',
  cpp: 'cpp',
  ruby: 'ruby',
  python: 'python',
};

const URL_PATTERN = new RegExp('('
  + '('
  + '([A-Za-z]{3,9}:(?:\\/\\/)?)'
  + '(?:[\\-;:&=\\+\\$,\\w]+@)?[A-Za-z0-9\\.\\-]+[A-Za-z0-9\\-]'
  + '|'
  + '(?:www\\.|[\\-;:&=\\+\\$,\\w]+@)'
  + '[A-Za-z0-9\\.\\-]+[A-Za-z0-9\\-]'
  + ')'
  + '('
  + '(?:\\/[\\+~%\\/\\.\\w\\-_]*)?'
  + '\\??(?:[\\-\\+=&;%@\\.\\w_\\/:]*)'
  + '#?(?:[\\.\\!\\/\\\\\\w]*)'
  + ')?'
  + ')');

/**
 * the converters works by identifying bbcode tags, and replace the tags with corresponding html. A Match instance specifies a pattern for identify some bbcode tags, and the rules to transform the tag to html.
 * @property {string} e - the reg expr that specifies the bbcode tag pattern.
 * @property {Function} func - the function that replaces the tag with html
 */
interface Match {
  e:string;
  func:(substring:string, ...args:any[]) => string;
}

/**
 * @function doReplace replace all bbcode tags in content, according to rules provided by matches
 * @param content {string} the bbcode content we want to convert to html
 * @param matches {Match[]} the rules that how bbcode tags are replaced with html tags
 */
function doReplace(content:string, matches:Match[]) {
  let hasMatch:boolean;
  // keeps replacing bbcode tags with html until there is no tag left
  do {
    hasMatch = false;
    for (let i = 0; i < matches.length; ++i) {
        const match = matches[i];
        const regex = new RegExp(match.e, 'gi');  // 'gi': global match, case insensitive
        const tmp = content.replace(regex, match.func);
        if (tmp !== content) {
            content = tmp;
            hasMatch = true;
        }
    }
  } while (hasMatch);
  return content;
}

/**
 * a helper method used to extract text within quotes
 * e.g. "some text" => some text
 */
function extractQuotedText(value:string, parts:string[]) {
  const quotes = ['\"', "'"];
  let nextPart;

  for (let i = 0; i < quotes.length; ++i) {
    const quote = quotes[i];
    if (value && value[0] === quote) {
        value = value.slice(1);
        if (value[value.length - 1] !== quote) {
            while (parts && parts.length) {
                nextPart = parts.shift();
                value += ' ' + nextPart;
                if (nextPart[nextPart.length - 1] === quote) {
                    break;
                }
            }
        }
        value = value.replace(new RegExp('[' + quote + ']+$'), '');
        break;
    }
  }
  return {value, remainingParts: parts};
}

/**
 * get all params in the tag
 *
 * e.g. for bbcode [size=12]some text[/size]
 *
 * the param is {size: 12}
 */
function parseParams (tagName:string, params) {
  let parts:string[];
  // let r:{value:string, parts:string[]};

  const paramMap:{[key:string]:string} = {};
  if (!params) {
    return paramMap;
  }

  params = params.replace(/\s*[=]\s*/g, '='); // strip spaces around '='
  parts = params.split(/\s+/);

  while (parts.length) {
    const part:string = (parts.shift()) as string;
    if (!URL_PATTERN.exec(part)) {
      const index = part.indexOf('=');
      if (index > 0) {
        const { value, remainingParts } = extractQuotedText(part.slice(index + 1), parts);
        paramMap[part.slice(0, index).toLowerCase()] = value;
        parts = remainingParts;
      } else {
        const { value, remainingParts } = extractQuotedText(part, parts);
        paramMap[tagName] = value;
        parts = remainingParts;
      }
    } else {
      const { value, remainingParts } = extractQuotedText(part, parts);
      paramMap[tagName] = value;
      parts = remainingParts;
    }
  }
  return paramMap;
}

export type BBCODETag = 'quote' | 'url' | 'email' | 'anchor' | 'b' | 'i' | 'size'
  | 'color' | 'highlight' | 'fly' | 'move' | 'indent' | 'font' | 'li'
  | 'ul' | 'ol' | 'code' | 'php' | 'java' | 'javascript' | 'cpp' | 'ruby'
  | 'python' | 'html' | 'mention' | 'span' | 'h1' | 'h2' | 'h3' | 'h4'
  | 'h5' | 'h6' | 'table' | 'tr' | 'td' | 's' | 'u' | 'sup' | 'sub'
  | 'youtube' | 'gvideo' | 'google' | 'baidu' | 'wikipedia' | 'img';

export type HTMLTag = 'div' | 'blockquote' | 'a' | 'strong' | 'em' | 'span'
  | 'marquee' | 'li' | 'ul' | 'ol' | 'pre' | 'h1' | 'h2' | 'h3' | 'h4'
  | 'h5' | 'h6' | 'table' | 'tr' | 'td' | 's' | 'u' | 'sup' | 'sub'
  | 'object' | 'embed' | 'img' | '';

// TODO: comment out unsupported bbcode tags
export const BBCDOE_HTML_TAG_MAP:{[key in BBCODETag]:HTMLTag} = {
  'quote': 'blockquote',
  'url': 'a',
  'email': 'a',
  'anchor': 'a',
  'b': 'strong',
  'i': 'em',
  'size': 'span',
  'color': 'span',
  'highlight': 'span',
  'fly': 'marquee',
  'move': 'marquee',
  'indent': 'blockquote',
  'font': 'span',
  'li': 'li',
  'ul': 'ul',
  'ol': 'ol',
  'code': 'pre',
  'php': 'pre',
  'java': 'pre',
  'javascript': 'pre',
  'cpp': 'pre',
  'ruby': 'pre',
  'python': 'pre',
  'html': '',
  'mention': 'span',
  'span': 'span',
  'h1': 'h1',
  'h2': 'h2',
  'h3': 'h3',
  'h4': 'h4',
  'h5': 'h5',
  'h6': 'h6',
  'table': 'table',
  'tr': 'tr',
  'td': 'td',
  's': 's',
  'u': 'u',
  'sup': 'sup',
  'sub': 'sub',
  'youtube': 'object',
  'gvideo': 'embed',
  'google': 'a',
  'baidu': 'a',
  'wikipedia': 'a',
  'img': 'img',
};
// TEST: what if none of them?

/**
 * the method is a instruction rule for how to replace bbcode tag with html
 *
 * it will be stored in Match.func
 */
function tagReplace(fullMatch, t:string, params, value) {
  const temp = t.toLowerCase();
  let tag:BBCODETag;
  if (BBCDOE_HTML_TAG_MAP[temp]) {
    // a valid BBCODETag
    tag = temp as BBCODETag;
  } else {
    // a invalid tag
    return `<${t}>${value}</${t}`;
  }

  if (!codeTags[tag]) {
    value = value.replace(/ /g, '&nbsp;');
  } else {
    value = value.replace(/<[\t ]*br[\t ]*\/>/g, '\n');
    value = value.replace(/</g, '&lt;');
    value = value.replace(/>/g, '&gt;');
  }

  params = parseParams(tag, params || undefined);
  const inlineValue = params[tag];

  switch (tag) {
    case 'quote':
    case 'b':
    case 'i':
    case 'move':
    case 'indent':
    case 'ul':
    case 'ol':
    case 'span':
    case 'h1':
    case 'h2':
    case 'h3':
    case 'h4':
    case 'h5':
    case 'h6':
    case 'table':
    case 'tr':
    case 'td':
    case 's':
    case 'u':
    case 'sup':
    case 'sub':
      return `<${BBCDOE_HTML_TAG_MAP[tag]}>${value}</${BBCDOE_HTML_TAG_MAP[tag]}>`;
    case 'url':
      return '<a target="_blank" rel="noopener noreferrer" href="' + (inlineValue || value) + '">' + value + '</a>';
    case 'email':
      return '<a class="' + options.classPrefix + 'link" target="_blank" href="mailto:' + (inlineValue || value) + '">' + value + '</a>';
    case 'anchor':
      return '<a name="' + (inlineValue || params.a || value) + '">' + value + '</a>';
    case 'size': {
      // TODO:
      return '<span class="ql-size-' + inlineValue + '">' + value + '</span>';
    }
    case 'color':
        return '<span style="color:' + inlineValue + '">' + value + '</span>';
    case 'highlight':
        return '<span style="background-color:' + inlineValue + '">' + value + '</span>';
    case 'fly':
        return '<marquee behavior="alternate">' + value + '</marquee>';
    case 'font':
      return '<span style="font-family:' + inlineValue + '">' + value + '</span>';
    case 'li': {
      let className = '';
      if (inlineValue && /indent-[\d]+/.test(inlineValue)) {
          className = options.classPrefix + inlineValue;
      }
      return `<li${className ? ` class="${className}"` : ''}>${value}</li>`;
    }
    case 'code':
    case 'php':
    case 'java':
    case 'javascript':
    case 'cpp':
    case 'ruby':
    case 'python':
        return '<pre class="' + options.classPrefix + (tag === 'code' ? '' : 'code_') + 'syntax' + '">' + value + '</pre>';
    case 'html':
        return value;
    case 'mention': {
      let val = '<span class="' + options.classPrefix + 'mention"';
      if (inlineValue) {
          val += ' data-mention-id="' + inlineValue + '"';
      }
      return val + '>' + (options.mentionPrefix || '') + value + '</span>';
    }
    case 'youtube':
        return '<object class="' + options.classPrefix + 'video" width="425" height="350"><param name="movie" value="http://www.youtube.com/v/' + value + '"></param><embed src="http://www.youtube.com/v/' + value + '" type="application/x-shockwave-flash" width="425" height="350"></embed></object>';
    case 'gvideo':
        return '<embed class="' + options.classPrefix + 'video" style="width:400px; height:325px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=' + value + '&amp;hl=en">';
    case 'google':
        return '<a class="' + options.classPrefix + 'link" target="_blank" href="http://www.google.com/search?q=' + (inlineValue || value) + '">' + value + '</a>';
    case 'baidu':
        return '<a class="' + options.classPrefix + 'link" target="_blank" href="http://www.baidu.com/s?wd=' + (inlineValue || value) + '">' + value + '</a>';
    case 'wikipedia':
        return '<a class="' + options.classPrefix + 'link" target="_blank" href="http://www.wikipedia.org/wiki/' + (inlineValue || value) + '">' + value + '</a>';
    case 'img': {
      let dims = new RegExp('^(\\d+)x(\\d+)$').exec(inlineValue || '');
      if (!dims || (dims.length !== 3)) {
            dims = new RegExp('^width=(\\d+)\\s+height=(\\d+)$').exec(inlineValue || '');
        }
      if (dims && dims.length === 3) {
            params = undefined;
        }
      let val = '<img class="' + options.classPrefix + 'image" src="' + value + '"';
      if (dims && dims.length === 3) {
            val += ' width="' + dims[1] + '" height="' + dims[2] + '"';
        } else {
            for (let i in params) {
                if (i === 'img') {
                    i = 'alt';
                }
                val += ' ' + i + '="' + params[i] + '"';
            }
        }
      return val + '/>';
    }
  }

  return fullMatch;
  }

/**
* 输出html内容
* @param content   你输入的内容
* @returns 展示的html
*/
export function bbcode2html (content) {
  const matches:Match[] = [];

  // remove auto generated new line after quote
  content = content.replace(/\[\/blockquote\][\n\s]+/g, '[/blockquote]');
  // remove auto generated new line after list item (within list)
  content = content.replace(/\[\/li\][\n\s]+/g, '[/li]');
  // remove auto generated new line after list (outside list)
  content = content.replace(/\[\/ol\][\ ]*[\n]/g, '[/ol]');
  content = content.replace(/\[\/ul\][\ ]*[\n]/g, '[/ul]');
  // preserve other new lines
  content = content.replace(/\n/g, '<br/>');

  matches.push({e: '\\[(\\w+)(?:[= ]([^\\]]+))?]((?:.|[\r\n])*?)\\[/\\1]', func: tagReplace});

  return doReplace(content, matches);
}
