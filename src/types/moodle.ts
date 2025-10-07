
export interface MoodleCredentials {
  url: string;
  token: string;
}

export interface MoodleCourse {
  id: number;
  shortname: string;
  fullname: string;
  displayname: string;
  summary: string;
  summaryformat: number;
  startdate: number;
  enddate: number;
  progress?: number; // Added progress property as optional
}

export interface MoodleStudent {
  id: number;
  username: string;
  firstname: string;
  lastname: string;
  fullname: string;
  email: string;
  department: string;
  firstaccess: number;
  lastaccess: number;
  profileimageurl: string;
}

export interface MoodleGrade {
  id: number;
  itemname: string;
  itemmodule: string;
  iteminstance: number;
  itemtype: string;
  graderaw: number;
  grademax: number;
  grademin: number;
  gradedatesubmitted: number;
  gradedategraded: number;
  feedback: string;
}

export interface MoodleAssignment {
  id: number;
  name: string;
  duedate: number;
  courseId?: number; // For mock data
  description?: string; // For mock data
}

// ============================================================================
// Submission Types
// ============================================================================

export interface MoodleSubmissionFile {
  filename: string;
  filepath: string;
  filesize: number;
  fileurl: string;
  timemodified: number;
  mimetype: string;
}

export interface MoodleSubmissionPlugin {
  type: string; // 'file', 'onlinetext', 'comments', etc.
  name: string;
  fileareas?: Array<{
    area: string;
    files: MoodleSubmissionFile[];
  }>;
  editorfields?: Array<{
    name: string;
    text: string;
    format: number;
  }>;
}

export interface MoodleSubmissionStatus {
  status: 'draft' | 'submitted' | 'reopened' | 'new';
  timemodified: number;
  timecreated: number;
}

export interface MoodleUserFlags {
  userid: number;
  mailed: number;
  extensionduedate: number;
  workflowstate: string;
  allocatedmarker: number;
}

export interface MoodleSubmission {
  id: number;
  userid: number;
  attemptnumber: number;
  timecreated: number;
  timemodified: number;
  status: 'draft' | 'submitted' | 'reopened' | 'new';
  groupid: number;
  assignment: number;
  latest: number;
  plugins?: MoodleSubmissionPlugin[];
  gradingstatus?: 'graded' | 'notgraded';
  grade?: number;
  gradefordisplay?: string;
}

// ============================================================================
// Grade Types
// ============================================================================

export interface MoodleGradeItem {
  id: number;
  itemname: string;
  itemtype: string;
  itemmodule: string;
  iteminstance: number;
  itemnumber: number;
  categoryid: number;
  outcomeid?: number;
  scaleid?: number;
  locked: boolean;
  cmid: number;
  courseid: number;
  weightraw?: number;
  weightformatted?: string;
  graderaw?: number;
  gradeformatted?: string;
  grademin: number;
  grademax: number;
  rangeformatted?: string;
  percentageformatted?: string;
  lettergradeformatted?: string;
  feedback?: string;
  feedbackformat?: number;
  gradedategraded?: number;
  gradedatesubmitted?: number;
  numusers?: number;
  averageformatted?: string;
}

export interface MoodleCourseGrade {
  courseid: number;
  grade: string;
  rawgrade: number;
}

export interface MoodleGradesResponse {
  usergrades: Array<{
    courseid: number;
    userid: number;
    userfullname: string;
    maxdepth: number;
    gradeitems: MoodleGradeItem[];
  }>;
}

// ============================================================================
// Enrollment Types
// ============================================================================

export interface MoodleEnrollment {
  id: number;
  shortname: string;
  fullname: string;
  displayname: string;
  enrolledusercount?: number;
  idnumber?: string;
  visible: number;
  summary?: string;
  summaryformat?: number;
  format?: string;
  showgrades?: boolean;
  lang?: string;
  enablecompletion?: boolean;
  completionhascriteria?: boolean;
  completionusertracked?: boolean;
  category?: number;
  progress?: number;
  completed?: boolean;
  startdate?: number;
  enddate?: number;
  marker?: number;
  lastaccess?: number;
  isfavourite?: boolean;
  hidden?: boolean;
  overviewfiles?: Array<{
    filename: string;
    filepath: string;
    filesize: number;
    fileurl: string;
    timemodified: number;
    mimetype: string;
  }>;
}

export interface MoodleCourseModule {
  id: number;
  url?: string;
  name: string;
  instance?: number;
  contextid?: number;
  visible?: number;
  uservisible?: boolean;
  availabilityinfo?: string;
  visibleoncoursepage?: number;
  modicon: string;
  modname: string;
  modplural: string;
  availability?: string;
  indent: number;
  onclick?: string;
  afterlink?: string;
  customdata?: string;
  noviewlink?: boolean;
  completion?: number;
  completiondata?: {
    state: number;
    timecompleted?: number;
    overrideby?: number;
    valueused?: boolean;
  };
  dates?: Array<{
    label: string;
    timestamp: number;
  }>;
  contents?: Array<{
    type: string;
    filename: string;
    filepath: string;
    filesize: number;
    fileurl: string;
    timecreated: number;
    timemodified: number;
    sortorder: number;
    mimetype: string;
    isexternalfile: boolean;
    userid?: number;
    author?: string;
    license?: string;
  }>;
}

export interface MoodleCourseContent {
  id: number;
  name: string;
  visible?: number;
  summary: string;
  summaryformat: number;
  section: number;
  hiddenbynumsections: number;
  uservisible?: boolean;
  availabilityinfo?: string;
  modules: MoodleCourseModule[];
}

export interface MoodleEnrolledUser {
  id: number;
  username?: string;
  firstname?: string;
  lastname?: string;
  fullname: string;
  email?: string;
  department?: string;
  firstaccess?: number;
  lastaccess?: number;
  lastcourseaccess?: number;
  description?: string;
  descriptionformat?: number;
  profileimageurlsmall?: string;
  profileimageurl: string;
  roles?: Array<{
    roleid: number;
    name: string;
    shortname: string;
    sortorder: number;
  }>;
  groups?: Array<{
    id: number;
    name: string;
    description?: string;
  }>;
  enrolledcourses?: Array<{
    id: number;
    fullname: string;
    shortname: string;
  }>;
}

// ============================================================================
// Quiz Types
// ============================================================================

export interface MoodleQuiz {
  id: number;
  course: number;
  coursemodule: number;
  name: string;
  intro?: string;
  introformat?: number;
  timeopen?: number;
  timeclose?: number;
  timelimit?: number;
  overduehandling?: string;
  graceperiod?: number;
  preferredbehaviour?: string;
  canredoquestions?: number;
  attempts?: number;
  attemptonlast?: number;
  grademethod?: number;
  decimalpoints?: number;
  questiondecimalpoints?: number;
  reviewattempt?: number;
  reviewcorrectness?: number;
  reviewmarks?: number;
  reviewspecificfeedback?: number;
  reviewgeneralfeedback?: number;
  reviewrightanswer?: number;
  reviewoverallfeedback?: number;
  questionsperpage?: number;
  navmethod?: string;
  shuffleanswers?: number;
  sumgrades?: number;
  grade?: number;
  timecreated?: number;
  timemodified?: number;
  password?: string;
  subnet?: string;
  browsersecurity?: string;
  delay1?: number;
  delay2?: number;
  showuserpicture?: number;
  showblocks?: number;
  completionattemptsexhausted?: number;
  completionpass?: number;
  allowofflineattempts?: number;
  autosaveperiod?: number;
  hasfeedback?: number;
  hasquestions?: number;
  section?: number;
  visible?: number;
  groupmode?: number;
  groupingid?: number;
}

export interface MoodleQuizAttempt {
  id: number;
  quiz: number;
  userid: number;
  attempt: number;
  uniqueid: number;
  layout: string;
  currentpage: number;
  preview: number;
  state: 'inprogress' | 'finished' | 'abandoned' | 'overdue';
  timestart: number;
  timefinish: number;
  timemodified: number;
  timemodifiedoffline: number;
  timecheckstate?: number;
  sumgrades?: number;
  gradednotificationsenttime?: number;
}

// ============================================================================
// Utility Types
// ============================================================================

export interface MoodleWarning {
  item?: string;
  itemid?: number;
  warningcode: string;
  message: string;
}
