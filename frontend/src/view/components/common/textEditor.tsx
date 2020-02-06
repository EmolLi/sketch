import * as React from 'react';
import 'react-quill/dist/quill.snow.css';
// import * as ReactQuill from 'react-quill';
import ReactQuill, { } from 'react-quill';
import {bbcode2html, html2bbcode, test} from '../../../utils/text-formater';

// export/ import bbcode methods based on https://github.com/anrip/quill-bbcode-ngx/blob/master/src/component/quill-editor.component.ts

// TODO: there are some lifecycle warnings with this component (e.g.omponentWillUpdate has been renamed), this warning is from the library Quill
// https://github.com/quilljs/quill/issues/2771
// Thre ReactQuill is a react wrapper for Quill, it also has this warning: https://github.com/zenoamaro/react-quill/pull/531
// however, the ReactQuill team is already working on fixing this, and the PR has already fixed the issue https://github.com/zenoamaro/react-quill/pull/549
// As it seems that the maintainer plans to merge this PR soon, I would prefer to wait for a while first. If not, I will clone the reactQuill module and try fix it myself Q.Q

// TODO: TEST
// [{ 'size': ['small', false, 'large', 'huge'] }], // fail -> use header for now
// [{ 'color': [] }, { 'background': [] }],
// ['bold', 'italic', 'underline', 'strike', 'blockquote'],
// [{'list': 'ordered'}, {'list': 'bullet'}, {'indent': '-1'}, {'indent': '+1'}],
// ['link', 'image'],
// ['clean']
const modules = {
  toolbar: [
    [{ 'size': ['small', false, 'large', 'huge'] }],
    [{ 'color': [] }, { 'background': [] }],
    ['bold', 'italic', 'underline', 'strike', 'blockquote'],
    [{'list': 'ordered'}, {'list': 'bullet'}, {'indent': '-1'}, {'indent': '+1'}],
    ['link', 'image'],
    ['clean'],
  ],
    history: {
      delay: 2000,
      maxStack: 200,
      userOnly: true,
    },
};

export type textFormat = 'plaintext' | 'markdown' | 'bbcode';

const formats = [
  'size',
  'color', 'background',
  'bold', 'italic', 'underline', 'strike', 'blockquote',
  'list', 'bullet', 'indent',
  'link', 'image',
];

export class TextEditor extends React.Component<{
  content?:string;
  isMarkdown?:boolean;
}, {
  text:string;
}> {
  constructor(props) {
    super(props);
    const text = this.setContent();
    this.state = { text }; // You can also pass a Quill Delta here
    this.handleChange = this.handleChange.bind(this);
  }

  private reactQuillRef:any = React.createRef<ReactQuill>();
  private quillRef:any = null;

  private attachQuillRefs = () => {
    if (typeof this.reactQuillRef.getEditor !== 'function') {
      return;
    }
    this.quillRef = this.reactQuillRef.getEditor();
    // console.log("ref", this.quillRef);
  }
  // return text in bbcode format
  public getContent () {
    const result = html2bbcode(this.state.text);
    return result;
  }

  private setContent () : string {
    const {content, isMarkdown} = this.props;
    if (content) {
      if (!isMarkdown) {
        const html = bbcode2html(content);
        return html;
        // this.reactQuillRef.clip
        // TODO
        // if (this.sanitize) {
          // this.content = this.domSanitizer.sanitize(SecurityContext.HTML, this.content);
      // }
      // const contents = this.quillEditor.clipboard.convert(this.content);
      // this.quillEditor.setContents(contents, 'silent');
      }
    }
    return '';
  }

  handleChange(value) {
    console.log(value);
    this.setState({ text: value });
  }

  render() {
    return (
      <ReactQuill value={this.state.text}
                  modules={modules}
                  formats={formats}
                  onChange={this.handleChange}
                  ref={this.reactQuillRef}/>
    );
  }
}