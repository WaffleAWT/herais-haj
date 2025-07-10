// Function to detect if we're in local development
function isLocalDevelopment() {
    return window.location.hostname === '127.0.0.1' || 
           window.location.hostname === 'localhost';
}

// Function to save application data and files
async function saveApplication(formData) {
    console.log('Starting application save process...');
    
    try {
        // Extract key data from formData
        const type = formData.get('type'); // visa, hajj, or umrah
        const formNumber = formData.get('formNumber');
        const pdfData = formData.get('pdfData');
        
        console.log(`Processing ${type} application #${formNumber}`);
        
        // Create directory structure
        const baseDir = `applications/${type}/${formNumber}`;
        const docsDir = `${baseDir}/documents`;
        
        // Log directories being created
        console.log('Creating directories:', {
            baseDir,
            docsDir
        });
        
        // Save PDF application
        const pdfFileName = `${baseDir}/application.pdf`;
        console.log('Saving PDF application to:', pdfFileName);
        
        // Save uploaded documents if they exist
        const savedFiles = ['application.pdf'];
        
        // Handle personal photo
        const personalPhoto = formData.get('personalPhoto');
        if (personalPhoto) {
            const photoExt = personalPhoto.name.split('.').pop();
            const photoPath = `${docsDir}/personal-photo.${photoExt}`;
            console.log('Saving personal photo:', photoPath);
            savedFiles.push(`documents/personal-photo.${photoExt}`);
        }
        
        // Handle passport copy
        const passportCopy = formData.get('passportCopy');
        if (passportCopy) {
            const passportExt = passportCopy.name.split('.').pop();
            const passportPath = `${docsDir}/passport-copy.${passportExt}`;
            console.log('Saving passport copy:', passportPath);
            savedFiles.push(`documents/passport-copy.${passportExt}`);
        }
        
        // Handle residency document if provided
        const residencyDoc = formData.get('residencyDoc');
        if (residencyDoc) {
            const residencyExt = residencyDoc.name.split('.').pop();
            const residencyPath = `${docsDir}/residency-document.${residencyExt}`;
            console.log('Saving residency document:', residencyPath);
            savedFiles.push(`documents/residency-document.${residencyExt}`);
        }

        // For development with Live Server, we'll simulate successful save
        // This will be replaced with actual server-side code in production
        if (window.location.protocol === 'http:' && window.location.hostname === '127.0.0.1') {
            console.log('Development environment detected - simulating successful save');
            return {
                success: true,
                applicationType: type,
                applicationNumber: formNumber,
                savedFiles: savedFiles,
                message: 'Application saved successfully (development mode)'
            };
        }

        // For production (Hostgator), we'll use the PHP endpoint
        const response = await fetch('save-application.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Server response:', result);

        return {
            success: true,
            applicationType: type,
            applicationNumber: formNumber,
            savedFiles: savedFiles,
            message: 'Application saved successfully'
        };

    } catch (error) {
        console.error('Error in saveApplication:', error);
        throw new Error(`Failed to save application: ${error.message}`);
    }
} 