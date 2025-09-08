<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\File;
use App\Repository\FileRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route('/api/files')]
class FileController extends AbstractController
{
    protected const DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/rtf',
        'application/zip',
        'application/x-rar-compressed',
        'application/json',
        'application/xml',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
    ];

    protected const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        'image/tiff',
        'image/tif',
        'image/heic',
        'image/heif',
    ];

    /**
     * Uploads a file to a folder or root.
     */
    /**
     * Uploads a file to a folder or root.
     *
     * @param Request                $request        The HTTP request containing the file
     * @param EntityManagerInterface $manager        The entity manager
     * @param int|null               $folderId       The ID of the folder to upload to (optional)
     * @param FileRepository         $fileRepository The file repository
     *
     * @return Response JSON response with the uploaded file data
     */
    #[Route('/upload/{folderId}', name: 'app_file_upload_to_folder', methods: ['POST'])]
    #[Route('/upload', name: 'app_file_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        EntityManagerInterface $manager,
        ?int $folderId,
        FileRepository $fileRepository,
    ): Response {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        if ($request->get('folderName')) {
            return $this->json(['error' => 'Folders cannot be uploaded'], 400);
        }

        $uploadedFile = new File();
        $uploadedFile->setOriginalName($file->getClientOriginalName());
        $uploadedFile->setSize($file->getSize());
        $uploadedFile->setMimeType($file->getMimeType());
        $uploadedFile->setOwner($this->getUser());
        $uploadedFile->setFile($file);

        if ($folderId) {
            /**
             * @var File $folder
             */
            $folder = $fileRepository->find($folderId);
            if (!$folder || $folder->getOwner() !== $this->getUser() || !$folder->isFolder()) {
                return $this->json(['error' => 'Folder not found'], 404);
            }
            $uploadedFile->setParent($folder);
        }

        $manager->persist($uploadedFile);
        $manager->flush();

        return $this->json(
            $uploadedFile,
            201,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Gets all files from the current user.
     */
    /**
     * Gets all files from the current user.
     *
     * @return Response JSON response with all files for the user
     */
    #[Route('/all', name: 'app_file_get_files_from_user', methods: ['GET'])]
    public function getFilesFromUser(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();
        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        return $this->json(
            $allFiles,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Downloads a file by its ID.
     */
    /**
     * Downloads a file by its ID.
     *
     * @param File|null $fileDownloadable The file entity to download
     *
     * @return Response The file download response
     */
    #[Route('/download/{id}', name: 'app_file_download', methods: ['GET'])]
    public function downloadDocument(
        ?File $fileDownloadable,
    ): Response {
        if (!$fileDownloadable) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($fileDownloadable->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        $file = sprintf('%s/public/%s', $this->getParameter('kernel.project_dir'), $fileDownloadable->getFilepath());

        return $this->file($file, $fileDownloadable->getOriginalName());
    }

    /**
     * Removes a file by its ID.
     */
    /**
     * Removes a file by its ID.
     *
     * @param File|null              $file    The file entity to remove
     * @param EntityManagerInterface $manager The entity manager
     *
     * @return Response JSON response indicating success or error
     */
    #[Route('/remove/{id}', name: 'app_file_delete', methods: ['DELETE'])]
    public function removeFile(?File $file, EntityManagerInterface $manager): Response
    {
        if (!$file) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        $manager->remove($file);
        $manager->flush();

        return $this->json(
            ['message' => 'File removed successfully'],
            200
        );
    }

    /**
     * Shares a file with specified users.
     */
    /**
     * Shares a file with specified users.
     *
     * @param File|null              $file           The file entity to share
     * @param Request                $request        The HTTP request containing user IDs
     * @param EntityManagerInterface $manager        The entity manager
     * @param UserRepository         $userRepository The user repository
     *
     * @return Response JSON response indicating success or error
     */
    #[Route('/share/{id}', name: 'app_file_share', methods: ['POST'])]
    public function shareFile(?File $file, Request $request, EntityManagerInterface $manager, UserRepository $userRepository): Response
    {
        if (!$file) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        $data = $request->toArray();
        $userIds = $data['userIds'] ?? [];

        foreach ($userIds as $userId) {
            $file->shareWith($userRepository->find($userId));
        }

        $manager->persist($file);
        $manager->flush();

        return $this->json(
            ['message' => 'File shared successfully'],
            200
        );
    }

    /**
     * Revokes file access for specified users.
     */
    /**
     * Revokes file access for specified users.
     *
     * @param File|null              $file           The file entity to revoke access
     * @param Request                $request        The HTTP request containing user IDs
     * @param EntityManagerInterface $manager        The entity manager
     * @param UserRepository         $userRepository The user repository
     *
     * @return Response JSON response indicating success or error
     */
    #[Route('/revoke/{id}', name: 'app_file_revoke', methods: ['POST'])]
    public function revokeFileAccess(?File $file, Request $request, EntityManagerInterface $manager, UserRepository $userRepository): Response
    {
        if (!$file) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        $data = $request->toArray();
        $userIds = $data['userIds'] ?? [];

        foreach ($userIds as $userId) {
            $file->revokeAccess($userRepository->find($userId));
        }

        $manager->persist($file);
        $manager->flush();

        return $this->json(
            ['message' => 'File access revoked successfully'],
            200
        );
    }

    /**
     * Gets file data by its ID.
     */
    /**
     * Gets file data by its ID.
     *
     * @param File|null $file The file entity to show
     *
     * @return Response JSON response with file data or error
     */
    #[Route('/show/{id}', name: 'app_file_data', methods: ['GET'])]
    public function getFileData(?File $file): Response
    {
        if (!$file) {
            return $this->json(['error' => 'File not found'], 404);
        }

        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'You are not the owner of this file'], 403);
        }

        return $this->json(
            $file,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Gets the most recent files for the current user.
     */
    /**
     * Gets the most recent files for the current user.
     *
     * @return Response JSON response with recent files
     */
    #[Route('/recent', name: 'app_file_recent', methods: ['GET'])]
    public function getRecentFiles(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();

        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        $sortedFiles = $allFiles->toArray();
        usort($sortedFiles, fn ($a, $b) => $b->getUploadedAt() <=> $a->getUploadedAt());

        $recentFiles = array_slice($sortedFiles, 0, 5);

        return $this->json(
            $recentFiles,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Gets all folders for the current user.
     */
    /**
     * Gets all folders for the current user.
     *
     * @return Response JSON response with folder files
     */
    #[Route('/folders', name: 'app_file_folders_index', methods: ['GET'])]
    public function getFoldersFiles(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();

        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        $sortedFiles = $allFiles->toArray();

        $foldersFile = array_filter($sortedFiles, fn ($file) => $file->isFolder());
        $foldersFile = array_values($foldersFile);

        return $this->json(
            $foldersFile,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Gets all document files for the current user.
     */
    /**
     * Gets all document files for the current user.
     *
     * @return Response JSON response with document files
     */
    #[Route('/documents', name: 'app_file_documents_index', methods: ['GET'])]
    public function getDocumentsFiles(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();

        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        $sortedFiles = $allFiles->toArray();

        $documentsFile = array_filter($sortedFiles, fn ($file) => in_array($file->getMimeType(), self::DOCUMENT_MIME_TYPES, true));
        $documentsFile = array_values($documentsFile);

        return $this->json(
            $documentsFile,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Gets all image files for the current user.
     */
    /**
     * Gets all image files for the current user.
     *
     * @return Response JSON response with image files
     */
    #[Route('/images', name: 'app_file_images_index', methods: ['GET'])]
    public function getImagesFiles(): Response
    {
        /** @var \App\Entity\User|null */
        $user = $this->getUser();
        $files = $user->getFiles();
        $sharedFiles = $user->getSharedFiles();

        $allFiles = new ArrayCollection(
            array_merge($files->toArray(), $sharedFiles->toArray())
        );

        $sortedFiles = $allFiles->toArray();

        $imagesFile = array_filter($sortedFiles, fn ($file) => in_array($file->getMimeType(), self::IMAGE_MIME_TYPES, true));
        $imagesFile = array_values($imagesFile);

        return $this->json(
            $imagesFile,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Creates a new folder for the current user.
     */
    /**
     * Creates a new folder for the current user.
     *
     * @param EntityManagerInterface $manager        The entity manager
     * @param Request                $request        The HTTP request containing folder data
     * @param FileRepository         $fileRepository The file repository
     *
     * @return Response JSON response with the created folder
     */
    #[Route('/folder/create', name: 'app_file_create_folder', methods: ['POST'])]
    public function createFolder(EntityManagerInterface $manager, Request $request, FileRepository $fileRepository): Response
    {
        $user = $this->getUser();

        $folder = new File();
        $data = json_decode($request->getContent(), true);
        $folder->setFilename($data['folderName'] ?? 'New folder');
        $folder->setOwner($user);
        $folder->setIsFolder(true);
        $folder->setMimeType('folder');

        $data = $request->toArray();
        $parentId = $data['parentId'] ?? null;

        if (!empty($parentId)) {
            $parent = $fileRepository->find($parentId);
            if (!$parent || !$parent->isFolder()) {
                return $this->json(['error' => 'Invalid parent folder'], 400);
            }
            $folder->setParent($parent);
        }

        $manager->persist($folder);
        $manager->flush();

        return $this->json(
            $folder,
            201,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Gets children files of a folder.
     */
    /**
     * Gets children files of a folder.
     *
     * @param File           $folder         The folder entity
     * @param FileRepository $fileRepository The file repository
     *
     * @return Response JSON response with children files or error
     */
    #[Route('/folder/{id}', name: 'app_folder_get_children', methods: ['GET'])]
    public function getChildren(File $folder, FileRepository $fileRepository): Response
    {
        if ($folder->isFolder() && $folder->getOwner() === $this->getUser()) {
            $children = $folder->getChildren();
        } else {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        return $this->json(
            $children,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Moves a file to a new parent folder.
     */
    /**
     * Moves a file to a new parent folder.
     *
     * @param File                   $file    The file entity to move
     * @param EntityManagerInterface $manager The entity manager
     * @param Request                $request The HTTP request containing new parent ID
     *
     * @return Response JSON response with the moved file
     */
    #[Route('/move/{id}', name: 'app_file_move', methods: ['POST'])]
    public function moveFile(File $file, EntityManagerInterface $manager, Request $request): Response
    {
        $data = $request->toArray();
        $newParentId = $data['newParentId'] ?? null;

        $file->setParent($newParentId);

        $manager->persist($file);
        $manager->flush();

        return $this->json(
            $file,
            200,
            [],
            ['groups' => ['file:read', 'user:read']]
        );
    }

    /**
     * Previews a file inline in the browser.
     */
    /**
     * Previews a file inline in the browser.
     *
     * @param File           $file           The file entity to preview
     * @param UploaderHelper $uploaderHelper The uploader helper
     *
     * @return Response The binary file response for preview
     */
    #[Route('/preview/{id}', name: 'file_preview', methods: ['GET'])]
    public function previewFile(File $file, UploaderHelper $uploaderHelper): Response
    {
        $filePath = $uploaderHelper->asset($file, 'file');

        if (!$filePath) {
            throw new NotFoundHttpException('File not found.');
        }

        $fullPath = $this->getParameter('kernel.project_dir').'/public'.$filePath;

        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException('File not found on disk.');
        }

        $response = new BinaryFileResponse($fullPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);

        return $response;
    }
}
