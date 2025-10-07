import axios from 'axios';
import {
  MoodleCourse,
  MoodleAssignment,
  MoodleStudent,
  MoodleSubmission,
  MoodleGradeItem,
  MoodleEnrollment,
  MoodleCourseContent,
  MoodleQuiz,
  MoodleQuizAttempt
} from '@/types/moodle';

const MOODLE_URL_KEY = 'moodle_url';
const MOODLE_TOKEN_KEY = 'moodle_token';

// Add this mock data for the test environment
const MOCK_COURSES = [
  {
    id: 101,
    fullname: "Introduction to Mathematics",
    shortname: "MATH101",
    summary: "Fundamental concepts of mathematics including algebra, calculus, and geometry.",
    startdate: Math.floor(Date.now() / 1000) - 30 * 24 * 60 * 60, // 30 days ago
    enddate: Math.floor(Date.now() / 1000) + 60 * 24 * 60 * 60,   // 60 days in future
    progress: 65
  },
  {
    id: 102,
    fullname: "English Literature",
    shortname: "ENG201",
    summary: "Analysis of classic and contemporary literary works.",
    startdate: Math.floor(Date.now() / 1000) - 15 * 24 * 60 * 60, // 15 days ago
    enddate: Math.floor(Date.now() / 1000) + 75 * 24 * 60 * 60,   // 75 days in future
    progress: 42
  },
  {
    id: 103,
    fullname: "Introduction to Biology",
    shortname: "BIO101",
    summary: "Study of living organisms and their interactions with ecosystems.",
    startdate: Math.floor(Date.now() / 1000) - 5 * 24 * 60 * 60,  // 5 days ago
    enddate: Math.floor(Date.now() / 1000) + 85 * 24 * 60 * 60,   // 85 days in future
    progress: 23
  },
  {
    id: 104,
    fullname: "World History",
    shortname: "HIST101",
    summary: "Overview of major historical events and civilizations.",
    startdate: Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60, // 10 days ago
    enddate: Math.floor(Date.now() / 1000) + 80 * 24 * 60 * 60,   // 80 days in future
    progress: 18
  },
  {
    id: 105,
    fullname: "Introduction to Computer Science",
    shortname: "CS101",
    summary: "Basics of programming, algorithms, and computer systems.",
    startdate: Math.floor(Date.now() / 1000) - 2 * 24 * 60 * 60,  // 2 days ago
    enddate: Math.floor(Date.now() / 1000) + 88 * 24 * 60 * 60,   // 88 days in future
    progress: 5
  }
];

const MOCK_ASSIGNMENTS = [
  {
    id: 201,
    name: "Math Homework 1",
    duedate: Math.floor(Date.now() / 1000) + 7 * 24 * 60 * 60, // 7 days from now
    courseId: 101,
    description: "Complete problems 1-20 in Chapter 3"
  },
  {
    id: 202,
    name: "English Essay",
    duedate: Math.floor(Date.now() / 1000) + 3 * 24 * 60 * 60, // 3 days from now
    courseId: 102,
    description: "Write a 5-page analysis of Shakespeare's Hamlet"
  },
  {
    id: 203,
    name: "Biology Lab Report",
    duedate: Math.floor(Date.now() / 1000) + 5 * 24 * 60 * 60, // 5 days from now
    courseId: 103,
    description: "Document your findings from the ecosystem observation"
  },
  {
    id: 204,
    name: "History Research Paper",
    duedate: Math.floor(Date.now() / 1000) + 14 * 24 * 60 * 60, // 14 days from now
    courseId: 104,
    description: "Research and write about a significant historical event"
  }
];

// Add mock students data
const MOCK_STUDENTS = [
  {
    id: 301,
    username: "student1",
    firstname: "John",
    lastname: "Doe",
    fullname: "John Doe",
    email: "john.doe@example.com",
    department: "Science",
    firstaccess: Math.floor(Date.now() / 1000) - 30 * 24 * 60 * 60,
    lastaccess: Math.floor(Date.now() / 1000) - 2 * 24 * 60 * 60,
    profileimageurl: ""
  },
  {
    id: 302,
    username: "student2",
    firstname: "Jane",
    lastname: "Smith",
    fullname: "Jane Smith",
    email: "jane.smith@example.com",
    department: "Arts",
    firstaccess: Math.floor(Date.now() / 1000) - 25 * 24 * 60 * 60,
    lastaccess: Math.floor(Date.now() / 1000) - 1 * 24 * 60 * 60,
    profileimageurl: ""
  },
  {
    id: 303,
    username: "student3",
    firstname: "Bob",
    lastname: "Johnson",
    fullname: "Bob Johnson",
    email: "bob.johnson@example.com",
    department: "Mathematics",
    firstaccess: Math.floor(Date.now() / 1000) - 20 * 24 * 60 * 60,
    lastaccess: Math.floor(Date.now() / 1000) - 3 * 24 * 60 * 60,
    profileimageurl: ""
  },
  {
    id: 304,
    username: "student4",
    firstname: "Alice",
    lastname: "Williams",
    fullname: "Alice Williams",
    email: "alice.williams@example.com",
    department: "Computer Science",
    firstaccess: Math.floor(Date.now() / 1000) - 18 * 24 * 60 * 60,
    lastaccess: Math.floor(Date.now() / 1000) - 1 * 24 * 60 * 60,
    profileimageurl: ""
  },
  {
    id: 305,
    username: "student5",
    firstname: "Charlie",
    lastname: "Brown",
    fullname: "Charlie Brown",
    email: "charlie.brown@example.com",
    department: "Physics",
    firstaccess: Math.floor(Date.now() / 1000) - 15 * 24 * 60 * 60,
    lastaccess: Math.floor(Date.now() / 1000) - 4 * 24 * 60 * 60,
    profileimageurl: ""
  }
];

// Mock Submissions Data
const MOCK_SUBMISSIONS: MoodleSubmission[] = [
  {
    id: 1001,
    userid: 301,
    attemptnumber: 1,
    timecreated: Math.floor(Date.now() / 1000) - 5 * 24 * 60 * 60, // 5 days ago
    timemodified: Math.floor(Date.now() / 1000) - 3 * 24 * 60 * 60, // 3 days ago
    status: 'submitted',
    groupid: 0,
    assignment: 201,
    latest: 1,
    gradingstatus: 'graded',
    grade: 85,
    gradefordisplay: '85 / 100',
    plugins: [
      {
        type: 'file',
        name: 'File submissions',
        fileareas: [
          {
            area: 'submission_files',
            files: [
              {
                filename: 'math_homework_1.pdf',
                filepath: '/',
                filesize: 245678,
                fileurl: 'https://example.com/pluginfile.php/123/file.pdf',
                timemodified: Math.floor(Date.now() / 1000) - 3 * 24 * 60 * 60,
                mimetype: 'application/pdf'
              }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 1002,
    userid: 301,
    attemptnumber: 1,
    timecreated: Math.floor(Date.now() / 1000) - 2 * 24 * 60 * 60, // 2 days ago
    timemodified: Math.floor(Date.now() / 1000) - 1 * 24 * 60 * 60, // 1 day ago
    status: 'submitted',
    groupid: 0,
    assignment: 202,
    latest: 1,
    gradingstatus: 'notgraded',
    plugins: [
      {
        type: 'onlinetext',
        name: 'Online text',
        editorfields: [
          {
            name: 'onlinetext',
            text: '<p>This is my essay on Shakespeare\'s Hamlet. The play explores themes of revenge, madness, and mortality...</p>',
            format: 1
          }
        ]
      }
    ]
  },
  {
    id: 1003,
    userid: 302,
    attemptnumber: 1,
    timecreated: Math.floor(Date.now() / 1000) - 6 * 24 * 60 * 60, // 6 days ago
    timemodified: Math.floor(Date.now() / 1000) - 4 * 24 * 60 * 60, // 4 days ago
    status: 'submitted',
    groupid: 0,
    assignment: 201,
    latest: 1,
    gradingstatus: 'graded',
    grade: 92,
    gradefordisplay: '92 / 100',
    plugins: [
      {
        type: 'file',
        name: 'File submissions',
        fileareas: [
          {
            area: 'submission_files',
            files: [
              {
                filename: 'math_solutions.pdf',
                filepath: '/',
                filesize: 312456,
                fileurl: 'https://example.com/pluginfile.php/124/file.pdf',
                timemodified: Math.floor(Date.now() / 1000) - 4 * 24 * 60 * 60,
                mimetype: 'application/pdf'
              }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 1004,
    userid: 303,
    attemptnumber: 1,
    timecreated: Math.floor(Date.now() / 1000) - 1 * 24 * 60 * 60, // 1 day ago
    timemodified: Math.floor(Date.now() / 1000) - 12 * 60 * 60, // 12 hours ago
    status: 'draft',
    groupid: 0,
    assignment: 203,
    latest: 1,
    gradingstatus: 'notgraded'
  },
  {
    id: 1005,
    userid: 304,
    attemptnumber: 1,
    timecreated: Math.floor(Date.now() / 1000) - 4 * 24 * 60 * 60, // 4 days ago
    timemodified: Math.floor(Date.now() / 1000) - 4 * 24 * 60 * 60, // 4 days ago
    status: 'submitted',
    groupid: 0,
    assignment: 203,
    latest: 1,
    gradingstatus: 'graded',
    grade: 88,
    gradefordisplay: '88 / 100',
    plugins: [
      {
        type: 'file',
        name: 'File submissions',
        fileareas: [
          {
            area: 'submission_files',
            files: [
              {
                filename: 'biology_lab_report.docx',
                filepath: '/',
                filesize: 189234,
                fileurl: 'https://example.com/pluginfile.php/125/file.docx',
                timemodified: Math.floor(Date.now() / 1000) - 4 * 24 * 60 * 60,
                mimetype: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
              }
            ]
          }
        ]
      }
    ]
  }
];

// Mock Grade Items Data
const MOCK_GRADE_ITEMS: MoodleGradeItem[] = [
  {
    id: 2001,
    itemname: 'Math Homework 1',
    itemtype: 'mod',
    itemmodule: 'assign',
    iteminstance: 201,
    itemnumber: 0,
    categoryid: 101,
    locked: false,
    cmid: 5001,
    courseid: 101,
    graderaw: 85,
    gradeformatted: '85.00',
    grademin: 0,
    grademax: 100,
    rangeformatted: '0–100',
    percentageformatted: '85.00 %',
    lettergradeformatted: 'B',
    feedback: 'Good work! Pay attention to showing all steps in problem 15.',
    feedbackformat: 1,
    gradedategraded: Math.floor(Date.now() / 1000) - 2 * 24 * 60 * 60, // 2 days ago
    gradedatesubmitted: Math.floor(Date.now() / 1000) - 3 * 24 * 60 * 60 // 3 days ago
  },
  {
    id: 2002,
    itemname: 'English Essay',
    itemtype: 'mod',
    itemmodule: 'assign',
    iteminstance: 202,
    itemnumber: 0,
    categoryid: 102,
    locked: false,
    cmid: 5002,
    courseid: 102,
    grademin: 0,
    grademax: 100,
    rangeformatted: '0–100',
    feedbackformat: 1,
    gradedatesubmitted: Math.floor(Date.now() / 1000) - 1 * 24 * 60 * 60 // 1 day ago
  },
  {
    id: 2003,
    itemname: 'Biology Lab Report',
    itemtype: 'mod',
    itemmodule: 'assign',
    iteminstance: 203,
    itemnumber: 0,
    categoryid: 103,
    locked: false,
    cmid: 5003,
    courseid: 103,
    graderaw: 88,
    gradeformatted: '88.00',
    grademin: 0,
    grademax: 100,
    rangeformatted: '0–100',
    percentageformatted: '88.00 %',
    lettergradeformatted: 'B+',
    feedback: 'Excellent observations and analysis. Well-structured report.',
    feedbackformat: 1,
    gradedategraded: Math.floor(Date.now() / 1000) - 1 * 24 * 60 * 60, // 1 day ago
    gradedatesubmitted: Math.floor(Date.now() / 1000) - 4 * 24 * 60 * 60 // 4 days ago
  },
  {
    id: 2004,
    itemname: 'Course Total',
    itemtype: 'course',
    itemmodule: '',
    iteminstance: 101,
    itemnumber: 0,
    categoryid: 101,
    locked: false,
    cmid: 0,
    courseid: 101,
    graderaw: 85,
    gradeformatted: '85.00',
    grademin: 0,
    grademax: 100,
    rangeformatted: '0–100',
    percentageformatted: '85.00 %',
    lettergradeformatted: 'B',
    feedbackformat: 1
  },
  {
    id: 2005,
    itemname: 'Midterm Quiz',
    itemtype: 'mod',
    itemmodule: 'quiz',
    iteminstance: 401,
    itemnumber: 0,
    categoryid: 101,
    locked: false,
    cmid: 5004,
    courseid: 101,
    graderaw: 78,
    gradeformatted: '78.00',
    grademin: 0,
    grademax: 100,
    rangeformatted: '0–100',
    percentageformatted: '78.00 %',
    lettergradeformatted: 'C+',
    feedback: 'Review the material on derivatives for better understanding.',
    feedbackformat: 1,
    gradedategraded: Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60, // 10 days ago
    gradedatesubmitted: Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60 // 10 days ago
  }
];

// Mock Enrollments Data
const MOCK_ENROLLMENTS: MoodleEnrollment[] = [
  {
    id: 101,
    shortname: 'MATH101',
    fullname: 'Introduction to Mathematics',
    displayname: 'Introduction to Mathematics',
    enrolledusercount: 25,
    idnumber: 'MATH101-2024',
    visible: 1,
    summary: 'Fundamental concepts of mathematics including algebra, calculus, and geometry.',
    summaryformat: 1,
    format: 'topics',
    showgrades: true,
    enablecompletion: true,
    completionhascriteria: true,
    completionusertracked: true,
    category: 1,
    progress: 65,
    completed: false,
    startdate: Math.floor(Date.now() / 1000) - 30 * 24 * 60 * 60, // 30 days ago
    enddate: Math.floor(Date.now() / 1000) + 60 * 24 * 60 * 60, // 60 days in future
    lastaccess: Math.floor(Date.now() / 1000) - 2 * 24 * 60 * 60, // 2 days ago
    isfavourite: true,
    hidden: false
  },
  {
    id: 102,
    shortname: 'ENG201',
    fullname: 'English Literature',
    displayname: 'English Literature',
    enrolledusercount: 20,
    idnumber: 'ENG201-2024',
    visible: 1,
    summary: 'Analysis of classic and contemporary literary works.',
    summaryformat: 1,
    format: 'topics',
    showgrades: true,
    enablecompletion: true,
    completionhascriteria: true,
    completionusertracked: true,
    category: 2,
    progress: 42,
    completed: false,
    startdate: Math.floor(Date.now() / 1000) - 15 * 24 * 60 * 60, // 15 days ago
    enddate: Math.floor(Date.now() / 1000) + 75 * 24 * 60 * 60, // 75 days in future
    lastaccess: Math.floor(Date.now() / 1000) - 1 * 24 * 60 * 60, // 1 day ago
    isfavourite: false,
    hidden: false
  },
  {
    id: 103,
    shortname: 'BIO101',
    fullname: 'Introduction to Biology',
    displayname: 'Introduction to Biology',
    enrolledusercount: 30,
    idnumber: 'BIO101-2024',
    visible: 1,
    summary: 'Study of living organisms and their interactions with ecosystems.',
    summaryformat: 1,
    format: 'topics',
    showgrades: true,
    enablecompletion: true,
    completionhascriteria: true,
    completionusertracked: true,
    category: 3,
    progress: 23,
    completed: false,
    startdate: Math.floor(Date.now() / 1000) - 5 * 24 * 60 * 60, // 5 days ago
    enddate: Math.floor(Date.now() / 1000) + 85 * 24 * 60 * 60, // 85 days in future
    lastaccess: Math.floor(Date.now() / 1000) - 4 * 24 * 60 * 60, // 4 days ago
    isfavourite: false,
    hidden: false
  }
];

// Mock Course Contents Data
const MOCK_COURSE_CONTENTS: MoodleCourseContent[] = [
  {
    id: 0,
    name: 'Week 1: Introduction',
    visible: 1,
    summary: 'Course overview and fundamental concepts',
    summaryformat: 1,
    section: 0,
    hiddenbynumsections: 0,
    uservisible: true,
    modules: [
      {
        id: 5001,
        url: 'https://example.com/mod/assign/view.php?id=5001',
        name: 'Math Homework 1',
        instance: 201,
        visible: 1,
        uservisible: true,
        modicon: 'https://example.com/theme/image.php/boost/assign/1/icon',
        modname: 'assign',
        modplural: 'Assignments',
        indent: 0,
        completion: 1,
        completiondata: {
          state: 1,
          timecompleted: Math.floor(Date.now() / 1000) - 3 * 24 * 60 * 60
        },
        dates: [
          {
            label: 'Due date',
            timestamp: Math.floor(Date.now() / 1000) + 7 * 24 * 60 * 60
          }
        ]
      },
      {
        id: 5004,
        url: 'https://example.com/mod/quiz/view.php?id=5004',
        name: 'Midterm Quiz',
        instance: 401,
        visible: 1,
        uservisible: true,
        modicon: 'https://example.com/theme/image.php/boost/quiz/1/icon',
        modname: 'quiz',
        modplural: 'Quizzes',
        indent: 0,
        completion: 1,
        completiondata: {
          state: 1,
          timecompleted: Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60
        },
        dates: [
          {
            label: 'Closes',
            timestamp: Math.floor(Date.now() / 1000) - 5 * 24 * 60 * 60
          }
        ]
      }
    ]
  },
  {
    id: 1,
    name: 'Week 2: Advanced Topics',
    visible: 1,
    summary: 'Building on fundamental concepts',
    summaryformat: 1,
    section: 1,
    hiddenbynumsections: 0,
    uservisible: true,
    modules: [
      {
        id: 5005,
        url: 'https://example.com/mod/resource/view.php?id=5005',
        name: 'Lecture Notes - Chapter 2',
        instance: 301,
        visible: 1,
        uservisible: true,
        modicon: 'https://example.com/theme/image.php/boost/resource/1/icon',
        modname: 'resource',
        modplural: 'Files',
        indent: 0,
        completion: 0,
        contents: [
          {
            type: 'file',
            filename: 'chapter2_notes.pdf',
            filepath: '/',
            filesize: 1234567,
            fileurl: 'https://example.com/pluginfile.php/301/mod_resource/content/1/chapter2_notes.pdf',
            timecreated: Math.floor(Date.now() / 1000) - 20 * 24 * 60 * 60,
            timemodified: Math.floor(Date.now() / 1000) - 20 * 24 * 60 * 60,
            sortorder: 1,
            mimetype: 'application/pdf',
            isexternalfile: false
          }
        ]
      }
    ]
  }
];

// Mock Quizzes Data
const MOCK_QUIZZES: MoodleQuiz[] = [
  {
    id: 401,
    course: 101,
    coursemodule: 5004,
    name: 'Midterm Quiz',
    intro: 'This quiz covers chapters 1-5.',
    introformat: 1,
    timeopen: Math.floor(Date.now() / 1000) - 15 * 24 * 60 * 60, // 15 days ago
    timeclose: Math.floor(Date.now() / 1000) - 5 * 24 * 60 * 60, // 5 days ago
    timelimit: 3600, // 1 hour
    preferredbehaviour: 'deferredfeedback',
    attempts: 2,
    grademethod: 1,
    sumgrades: 100,
    grade: 100,
    questionsperpage: 1,
    navmethod: 'free',
    shuffleanswers: 1,
    section: 0,
    visible: 1,
    groupmode: 0,
    hasquestions: 1,
    hasfeedback: 1
  },
  {
    id: 402,
    course: 102,
    coursemodule: 5006,
    name: 'Literature Quiz 1',
    intro: 'Test your knowledge of Shakespeare.',
    introformat: 1,
    timeopen: Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60, // 10 days ago
    timeclose: Math.floor(Date.now() / 1000) + 5 * 24 * 60 * 60, // 5 days in future
    timelimit: 1800, // 30 minutes
    preferredbehaviour: 'deferredfeedback',
    attempts: 1,
    grademethod: 1,
    sumgrades: 50,
    grade: 50,
    questionsperpage: 5,
    navmethod: 'free',
    shuffleanswers: 1,
    section: 0,
    visible: 1,
    groupmode: 0,
    hasquestions: 1,
    hasfeedback: 1
  }
];

// Mock Quiz Attempts Data
const MOCK_QUIZ_ATTEMPTS: MoodleQuizAttempt[] = [
  {
    id: 6001,
    quiz: 401,
    userid: 301,
    attempt: 1,
    uniqueid: 123456,
    layout: '1,2,3,4,5,0',
    currentpage: 0,
    preview: 0,
    state: 'finished',
    timestart: Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60,
    timefinish: Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60 + 3000,
    timemodified: Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60 + 3000,
    timemodifiedoffline: 0,
    sumgrades: 78
  },
  {
    id: 6002,
    quiz: 402,
    userid: 301,
    attempt: 1,
    uniqueid: 123457,
    layout: '1,2,3,4,5,6,7,8,9,10,0',
    currentpage: 5,
    preview: 0,
    state: 'inprogress',
    timestart: Math.floor(Date.now() / 1000) - 1 * 60 * 60, // 1 hour ago
    timefinish: 0,
    timemodified: Math.floor(Date.now() / 1000) - 30 * 60, // 30 minutes ago
    timemodifiedoffline: 0
  }
];

interface CoreCourseGetCoursesParams {
  options?: {
    ids?: number[];
  };
}

class MoodleApiService {
  private moodleUrl: string | null = null;
  private moodleToken: string | null = null;

  constructor() {
    this.moodleUrl = localStorage.getItem(MOODLE_URL_KEY);
    this.moodleToken = localStorage.getItem(MOODLE_TOKEN_KEY);
  }

  hasCredentials(): boolean {
    return !!(this.moodleUrl && this.moodleToken);
  }

  setCredentials(url: string, token: string): void {
    this.moodleUrl = url;
    this.moodleToken = token;
    localStorage.setItem(MOODLE_URL_KEY, url);
    localStorage.setItem(MOODLE_TOKEN_KEY, token);
  }

  async coreCourseGetCourses(params?: CoreCourseGetCoursesParams): Promise<MoodleCourse[]> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'core_course_get_courses',
      moodlewsrestformat: 'json',
      ...params?.options?.ids && { 'options[ids]': params.options.ids.join(',') }
    });

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching courses:', error);
      throw error;
    }
  }

  async modAssignGetAssignments(courseIds: number[] = []): Promise<{ courses: { assignments: MoodleAssignment[] }[] }> {
     if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'mod_assign_get_assignments',
      moodlewsrestformat: 'json',
    });

    courseIds.forEach((courseId, index) => {
      data.append(`courseids[${index}]`, courseId.toString());
    });

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching assignments:', error);
      throw error;
    }
  }

  isTestEnvironment() {
    const user = localStorage.getItem('moodle_hub_user');
    if (user) {
      const userData = JSON.parse(user);
      return userData.email === 'teacher@test.com' || userData.email === 'student@test.com';
    }
    return false;
  }

  async getCourses() {
    if (this.isTestEnvironment()) {
      return Promise.resolve(MOCK_COURSES as MoodleCourse[]);
    }
    
    try {
      const courses = await this.coreCourseGetCourses();
      return courses;
    } catch (error) {
      console.error('Error fetching courses:', error);
      return [];
    }
  }

  async getAssignments(courseId?: number) {
    if (this.isTestEnvironment()) {
      if (courseId) {
        return Promise.resolve(MOCK_ASSIGNMENTS.filter(a => a.courseId === courseId));
      }
      return Promise.resolve(MOCK_ASSIGNMENTS);
    }
    
    try {
      let courseIds: number[] = [];
      if (courseId) {
        courseIds = [courseId];
      } else {
        const courses = await this.getCourses();
        courseIds = courses.map(course => course.id);
      }
      const assignmentsData = await this.modAssignGetAssignments(courseIds);
      
      let assignments: MoodleAssignment[] = [];
      assignmentsData.courses.forEach(course => {
        if (course.assignments) {
          assignments = assignments.concat(course.assignments);
        }
      });
      
      return assignments;
    } catch (error) {
      console.error('Error fetching assignments:', error);
      return [];
    }
  }

  async getAllStudents(): Promise<MoodleStudent[]> {
    if (this.isTestEnvironment()) {
      return Promise.resolve(MOCK_STUDENTS);
    }

    // In a real implementation, we would call the Moodle API here
    // For now, this is just a placeholder that returns an empty array
    try {
      if (!this.moodleUrl || !this.moodleToken) {
        throw new Error('Moodle URL and token are not set.');
      }

      const url = `${this.moodleUrl}/webservice/rest/server.php`;
      const data = new URLSearchParams({
        wstoken: this.moodleToken,
        wsfunction: 'core_user_get_users',
        moodlewsrestformat: 'json',
      });

      const response = await axios.post(url, data);
      return response.data?.users || [];
    } catch (error) {
      console.error('Error fetching students:', error);
      return [];
    }
  }

  // ============================================================================
  // Assignment Submission Methods
  // ============================================================================

  /**
   * Get submissions for specified assignments
   * @param assignmentIds Array of assignment IDs
   * @returns Promise with submissions data grouped by assignment
   */
  async modAssignGetSubmissions(assignmentIds: number[]): Promise<{
    assignments: Array<{
      assignmentid: number;
      submissions: MoodleSubmission[];
    }>;
  }> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'mod_assign_get_submissions',
      moodlewsrestformat: 'json',
    });

    assignmentIds.forEach((id, index) => {
      data.append(`assignmentids[${index}]`, id.toString());
    });

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching submissions:', error);
      throw error;
    }
  }

  /**
   * Get submission status for a specific assignment
   * @param assignmentId Assignment ID
   * @param userId Optional user ID (defaults to current user)
   * @returns Promise with detailed submission status
   */
  async modAssignGetSubmissionStatus(assignmentId: number, userId?: number): Promise<any> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'mod_assign_get_submission_status',
      moodlewsrestformat: 'json',
      assignid: assignmentId.toString(),
    });

    if (userId) {
      data.append('userid', userId.toString());
    }

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching submission status:', error);
      throw error;
    }
  }

  /**
   * Get user flags (extensions, workflow states) for assignments
   * @param assignmentIds Array of assignment IDs
   * @returns Promise with user flags data
   */
  async modAssignGetUserFlags(assignmentIds: number[]): Promise<{
    assignments: Array<{
      assignmentid: number;
      userflags: MoodleUserFlags[];
    }>;
  }> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'mod_assign_get_user_flags',
      moodlewsrestformat: 'json',
    });

    assignmentIds.forEach((id, index) => {
      data.append(`assignmentids[${index}]`, id.toString());
    });

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching user flags:', error);
      throw error;
    }
  }

  // ============================================================================
  // Grade Methods
  // ============================================================================

  /**
   * Get grade items for a user in a specific course
   * @param courseId Course ID
   * @param userId Optional user ID (defaults to current user)
   * @returns Promise with grade items
   */
  async gradereportUserGetGradeItems(courseId: number, userId?: number): Promise<MoodleGradesResponse> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'gradereport_user_get_grade_items',
      moodlewsrestformat: 'json',
      courseid: courseId.toString(),
    });

    if (userId) {
      data.append('userid', userId.toString());
    }

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching grade items:', error);
      throw error;
    }
  }

  /**
   * Get course grades overview for a user
   * @param userId Optional user ID (defaults to current user)
   * @returns Promise with course grades
   */
  async gradereportOverviewGetCourseGrades(userId?: number): Promise<{
    grades: MoodleCourseGrade[];
  }> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'gradereport_overview_get_course_grades',
      moodlewsrestformat: 'json',
    });

    if (userId) {
      data.append('userid', userId.toString());
    }

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching course grades:', error);
      throw error;
    }
  }

  /**
   * Get grades for a specific activity
   * @param component Component name (e.g., 'mod_assign')
   * @param activityId Activity instance ID
   * @param userIds Optional array of user IDs
   * @returns Promise with grades data
   */
  async coreGradesGetGrades(
    component: string,
    activityId: number,
    userIds?: number[]
  ): Promise<any> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'core_grades_get_grades',
      moodlewsrestformat: 'json',
      component: component,
      activityid: activityId.toString(),
    });

    if (userIds && userIds.length > 0) {
      userIds.forEach((id, index) => {
        data.append(`userids[${index}]`, id.toString());
      });
    }

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching grades:', error);
      throw error;
    }
  }

  // ============================================================================
  // Enrollment Methods
  // ============================================================================

  /**
   * Get courses a user is enrolled in
   * @param userId User ID
   * @returns Promise with enrolled courses
   */
  async coreEnrolGetUsersCourses(userId: number): Promise<MoodleEnrollment[]> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'core_enrol_get_users_courses',
      moodlewsrestformat: 'json',
      userid: userId.toString(),
    });

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching user courses:', error);
      throw error;
    }
  }

  /**
   * Get course contents (sections, modules, activities)
   * @param courseId Course ID
   * @returns Promise with course structure
   */
  async coreCourseGetContents(courseId: number): Promise<MoodleCourseContent[]> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'core_course_get_contents',
      moodlewsrestformat: 'json',
      courseid: courseId.toString(),
    });

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching course contents:', error);
      throw error;
    }
  }

  /**
   * Get enrolled users in a course
   * @param courseId Course ID
   * @param options Optional parameters (role filters, etc.)
   * @returns Promise with enrolled users
   */
  async coreEnrolGetEnrolledUsers(
    courseId: number,
    options?: { [key: string]: any }
  ): Promise<MoodleEnrolledUser[]> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'core_enrol_get_enrolled_users',
      moodlewsrestformat: 'json',
      courseid: courseId.toString(),
    });

    if (options) {
      Object.keys(options).forEach((key) => {
        data.append(`options[0][name]`, key);
        data.append(`options[0][value]`, options[key].toString());
      });
    }

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching enrolled users:', error);
      throw error;
    }
  }

  // ============================================================================
  // Quiz Methods
  // ============================================================================

  /**
   * Get quizzes in specified courses
   * @param courseIds Array of course IDs (empty array returns all accessible quizzes)
   * @returns Promise with quizzes data
   */
  async modQuizGetQuizzesByCourses(courseIds: number[]): Promise<{
    quizzes: MoodleQuiz[];
  }> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'mod_quiz_get_quizzes_by_courses',
      moodlewsrestformat: 'json',
    });

    courseIds.forEach((id, index) => {
      data.append(`courseids[${index}]`, id.toString());
    });

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching quizzes:', error);
      throw error;
    }
  }

  /**
   * Get quiz attempts for a user
   * @param quizId Quiz ID
   * @param userId Optional user ID (defaults to current user)
   * @param status Optional status filter ('inprogress', 'finished', 'abandoned')
   * @returns Promise with quiz attempts
   */
  async modQuizGetUserAttempts(
    quizId: number,
    userId?: number,
    status?: string
  ): Promise<{ attempts: MoodleQuizAttempt[] }> {
    if (!this.moodleUrl || !this.moodleToken) {
      throw new Error('Moodle URL and token are not set.');
    }

    const url = `${this.moodleUrl}/webservice/rest/server.php`;
    const data = new URLSearchParams({
      wstoken: this.moodleToken,
      wsfunction: 'mod_quiz_get_user_attempts',
      moodlewsrestformat: 'json',
      quizid: quizId.toString(),
    });

    if (userId) {
      data.append('userid', userId.toString());
    }

    if (status) {
      data.append('status', status);
    }

    try {
      const response = await axios.post(url, data);
      return response.data;
    } catch (error: any) {
      console.error('Error fetching quiz attempts:', error);
      throw error;
    }
  }

  // ============================================================================
  // High-Level Wrapper Methods (with Mock Data Support)
  // ============================================================================

  /**
   * Get all submissions for a course
   * @param courseId Course ID
   * @returns Promise with submissions array
   */
  async getSubmissionsByCourse(courseId: number): Promise<MoodleSubmission[]> {
    if (this.isTestEnvironment()) {
      return Promise.resolve(
        MOCK_SUBMISSIONS.filter((s) => {
          const assignment = MOCK_ASSIGNMENTS.find((a) => a.id === s.assignment);
          return assignment?.courseId === courseId;
        })
      );
    }

    try {
      const assignments = await this.getAssignments(courseId);
      if (assignments.length === 0) {
        return [];
      }

      const assignmentIds = assignments.map((a) => a.id);
      const submissionsData = await this.modAssignGetSubmissions(assignmentIds);

      let allSubmissions: MoodleSubmission[] = [];
      submissionsData.assignments.forEach((assignmentData) => {
        if (assignmentData.submissions) {
          allSubmissions = allSubmissions.concat(assignmentData.submissions);
        }
      });

      return allSubmissions;
    } catch (error) {
      console.error('Error fetching submissions by course:', error);
      return [];
    }
  }

  /**
   * Get grade items for a user in a course
   * @param courseId Course ID
   * @param userId Optional user ID (defaults to current user)
   * @returns Promise with grade items array
   */
  async getUserGrades(courseId: number, userId?: number): Promise<MoodleGradeItem[]> {
    if (this.isTestEnvironment()) {
      return Promise.resolve(
        MOCK_GRADE_ITEMS.filter((g) => g.courseid === courseId)
      );
    }

    try {
      const gradesResponse = await this.gradereportUserGetGradeItems(courseId, userId);

      if (!gradesResponse.usergrades || gradesResponse.usergrades.length === 0) {
        return [];
      }

      return gradesResponse.usergrades[0].gradeitems || [];
    } catch (error) {
      console.error('Error fetching user grades:', error);
      return [];
    }
  }

  /**
   * Get user's course enrollments
   * @param userId User ID
   * @returns Promise with enrollments array
   */
  async getUserEnrollments(userId: number): Promise<MoodleEnrollment[]> {
    if (this.isTestEnvironment()) {
      return Promise.resolve(MOCK_ENROLLMENTS);
    }

    try {
      const enrollments = await this.coreEnrolGetUsersCourses(userId);
      return enrollments;
    } catch (error) {
      console.error('Error fetching user enrollments:', error);
      return [];
    }
  }

  /**
   * Get course structure (sections and modules)
   * @param courseId Course ID
   * @returns Promise with course contents array
   */
  async getCourseStructure(courseId: number): Promise<MoodleCourseContent[]> {
    if (this.isTestEnvironment()) {
      return Promise.resolve(MOCK_COURSE_CONTENTS);
    }

    try {
      const contents = await this.coreCourseGetContents(courseId);
      return contents;
    } catch (error) {
      console.error('Error fetching course structure:', error);
      return [];
    }
  }

  /**
   * Get students enrolled in a course
   * @param courseId Course ID
   * @returns Promise with enrolled users array
   */
  async getCourseStudents(courseId: number): Promise<MoodleEnrolledUser[]> {
    if (this.isTestEnvironment()) {
      return Promise.resolve(
        MOCK_STUDENTS.map((s) => ({
          ...s,
          roles: [{ roleid: 5, name: 'Student', shortname: 'student', sortorder: 5 }],
        }))
      );
    }

    try {
      const users = await this.coreEnrolGetEnrolledUsers(courseId);
      // Filter to only students (role shortname 'student')
      return users.filter((u) =>
        u.roles?.some((r) => r.shortname === 'student')
      );
    } catch (error) {
      console.error('Error fetching course students:', error);
      return [];
    }
  }

  /**
   * Get quizzes for a course
   * @param courseId Course ID
   * @returns Promise with quizzes array
   */
  async getQuizzesByCourse(courseId: number): Promise<MoodleQuiz[]> {
    if (this.isTestEnvironment()) {
      return Promise.resolve(MOCK_QUIZZES.filter((q) => q.course === courseId));
    }

    try {
      const quizzesData = await this.modQuizGetQuizzesByCourses([courseId]);
      return quizzesData.quizzes || [];
    } catch (error) {
      console.error('Error fetching quizzes by course:', error);
      return [];
    }
  }
}

export const moodleApi = new MoodleApiService();
