import React, { useState, useEffect, useCallback } from 'react';
import { MainLayout } from "@/components/layout/main-layout";
import { useNavigate } from "react-router-dom";
import axios from 'axios';
import { supabase } from "@/integrations/supabase/client";
import { useAuth } from "@/contexts/AuthContext";
import {
  Box,
  Button,
  CircularProgress,
  List,
  ListItem,
  ListItemButton,
  ListItemText,
  Typography,
} from '@mui/material';
import SchoolSubmissions from './teacher/SchoolSubmissions';

type Submission = {
  courseId: string;
  submissionName: string;
  studentName: string;
  studentUsername: string;
  studentEmail: string;
  dateSubmitted: string;
  directLink: string;
};

type Report = {
  schoolName: string;
  googleSheetsLink: string;
  updatedAt: string;
  errorMessage?: string;
  submissions: Submission[];
};

export default function TeacherGrades() {
  const navigate = useNavigate();
  const { authState } = useAuth();
  const [reports, setReports] = useState<Report[]>([]);
  const [accessibleSchools, setAccessibleSchools] = useState<string[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [submissionsLoading, setSubmissionsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedSchool, setSelectedSchool] = useState<string | null>(null);

  // Memoize fetchUserProfile to prevent unnecessary re-renders
  const fetchUserProfile = useCallback(async () => {
    if (!authState.user?.id || !authState.isAuthenticated) {
      setAccessibleSchools([]);
      setLoading(false);
      return;
    }

    try {
      const { data: profiles, error } = await supabase
        .from('profiles')
        .select('accessible_schools')
        .eq('id', authState.user.id)
        .single(); // Use single() to get one record

      if (error) {
        console.error('Supabase error:', error);
        setError('Failed to fetch user profile');
        setAccessibleSchools([]);
        return;
      }

      const schools = profiles?.accessible_schools || [];
      setAccessibleSchools(schools);
    } catch (error) {
      console.error('Unexpected error fetching profile:', error);
      setError('Unexpected error fetching profile');
      setAccessibleSchools([]);
    } finally {
      setLoading(false);
    }
  }, [authState.user?.id, authState.isAuthenticated]);

  // Memoize fetchReports to prevent unnecessary re-renders
  const fetchReports = useCallback(async () => {
    try {
      setLoading(true);
      const response = await axios.get('https://ungradedassignmentsendpoint.myeducrm.net/reports');
      
      const fetchedReports: Report[] = response.data;
      // Sort submissions by date (oldest first) for each report
      const reportsWithSortedSubmissions = fetchedReports.map(report => ({
        ...report,
        submissions: report.submissions.sort((a: Submission, b: Submission) => 
          new Date(a.dateSubmitted).getTime() - new Date(b.dateSubmitted).getTime()
        )
      }));

      setReports(reportsWithSortedSubmissions);
    } catch (err) {
      console.error('Error fetching reports:', err);
      setError('Failed to fetch reports');
    } finally {
      setLoading(false);
    }
  }, []);

  // Memoize fetchSchoolReport
  const fetchSchoolReport = useCallback(async (schoolName: string) => {
    try {
      setSubmissionsLoading(true);
      const response = await axios.get('https://ungradedassignmentsendpoint.myeducrm.net/reports');
      
      const updatedReport = response.data.find((r: Report) => r.schoolName === schoolName);
      if (updatedReport) {
        const sortedReport = {
          ...updatedReport,
          submissions: updatedReport.submissions.sort((a: Submission, b: Submission) => 
            new Date(a.dateSubmitted).getTime() - new Date(b.dateSubmitted).getTime()
          )
        };
        
        setReports((prev) =>
          prev.map((r) => (r.schoolName === schoolName ? sortedReport : r))
        );
      }
    } catch (err) {
      console.error('Error refreshing report:', err);
      setError(`Failed to refresh report for ${schoolName}`);
    } finally {
      setSubmissionsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchUserProfile();
  }, [fetchUserProfile]);

  useEffect(() => {
    fetchReports();
  }, [fetchReports]);

  const handleSchoolSelect = (schoolName: string) => {
    if (loading || reports.length === 0) return;
    setSelectedSchool(schoolName);
  };

  const handleBackToList = () => {
    setSelectedSchool(null);
    setSubmissionsLoading(false);
  };

  const handleNavigateToReports = () => {
    navigate("/teacher/reports");
  };

  if (loading) {
    return (
      <MainLayout requiredRole="teacher">
        <Box display="flex" justifyContent="center" alignItems="center" minHeight="100vh">
          <CircularProgress size={60} thickness={4} />
        </Box>
      </MainLayout>
    );
  }

  if (error) {
    return (
      <MainLayout requiredRole="teacher">
        <Box m={2}>
          <Typography color="error">{error}</Typography>
          <Button
            variant="outlined"
            color="primary"
            onClick={() => {
              setError(null);
              fetchUserProfile();
              fetchReports();
            }}
            sx={{ mt: 2 }}
          >
            Retry
          </Button>
        </Box>
      </MainLayout>
    );
  }

  if (selectedSchool) {
    const report = reports.find((r) => r && r.schoolName === selectedSchool);
    if (!report) {
      return (
        <MainLayout requiredRole="teacher">
          <Box m={2}>
            <CircularProgress size={60} thickness={4} />
            <Button
              variant="outlined"
              color="primary"
              onClick={handleBackToList}
              sx={{ mt: 2 }}
            >
              Back to School List
            </Button>
          </Box>
        </MainLayout>
      );
    }
    return (
      <MainLayout requiredRole="teacher">
        <SchoolSubmissions
          report={report}
          onBack={handleBackToList}
          onRefresh={() => fetchSchoolReport(selectedSchool)}
        />
        {submissionsLoading && (
          <Box 
            display="flex" 
            justifyContent="center" 
            alignItems="center" 
            position="absolute"
            top={0}
            left={0}
            right={0}
            bottom={0}
            sx={{ backgroundColor: 'rgba(255, 255, 255, 0.8)' }}
          >
            <CircularProgress size={60} thickness={4} />
          </Box>
        )}
      </MainLayout>
    );
  }

  return (
    <MainLayout requiredRole="teacher">
      <Box className="mx-4 my-8 max-w-2xl mx-auto">
        <Typography variant="h4" className="text-3xl font-bold text-gray-800 mb-6">
          Select a School
        </Typography>
        {accessibleSchools.length === 0 ? (
          <Typography color="error">No schools available</Typography>
        ) : (
          <>
            <Typography variant="body2" className="text-gray-600 mb-4">
              Available schools: {accessibleSchools.length}
            </Typography>
            <List className="bg-white shadow-lg rounded-lg">
              {accessibleSchools.map((schoolName) => (
                <ListItem key={schoolName} className="border-b last:border-b-0">
                  <ListItemButton
                    onClick={!loading ? () => handleSchoolSelect(schoolName) : undefined}
                    disabled={loading || reports.length === 0}
                    className="hover:bg-blue-50 transition-colors duration-200 py-4 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <ListItemText
                      primary={schoolName}
                      className="text-lg font-medium text-gray-700"
                    />
                  </ListItemButton>
                </ListItem>
              ))}
            </List>
          </>
        )}
      </Box>
    </MainLayout>
  );
}